<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PIC = 'pic';

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, 'ADMIN_UNIT', 'admin_unit'], true);
    }

    public function isPic(): bool
    {
        return $this->role === self::ROLE_PIC;
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Ketua Tim',
            self::ROLE_PIC => 'PIC Laboratorium',
            default => ucfirst((string) ($this->role ?? 'Pengguna')),
        };
    }

    public function getRoleShortLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Ketua Tim',
            self::ROLE_PIC => 'PIC Lab',
            default => ucfirst((string) ($this->role ?? 'Pengguna')),
        };
    }

    public function getInitialsAttribute(): string
    {
        preg_match_all('/\p{L}+/u', (string) ($this->name ?? ''), $matches);
        $words = $matches[0] ?? [];

        if (empty($words)) {
            return 'U';
        }

        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 2, 'UTF-8'), 'UTF-8');
        }

        return mb_strtoupper(
            mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr($words[1], 0, 1, 'UTF-8'),
            'UTF-8'
        );
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'badge-role-admin',
            self::ROLE_PIC => 'badge-role-pic',
            default => 'badge-role-default',
        };
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'created_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'created_by');
    }

    public function lpks(): HasMany
    {
        return $this->hasMany(Lpk::class, 'pic_id');
    }

    public function memberLpks(): BelongsToMany
    {
        return $this->belongsToMany(Lpk::class, 'lpk_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Akun viewer yang ditautkan ke akun ini (diberikan izin membaca seluruh data LPK, asesmen, dan kalender kita).
     */
    public function linkedViewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_account_links', 'user_id', 'viewer_id')
            ->withTimestamps();
    }

    /**
     * Akun pemilik data yang menautkan kita sebagai viewer (memberi kita izin membaca seluruh data LPK mereka).
     */
    public function linkedOwners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_account_links', 'viewer_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Dapatkan daftar ID pemilik akun yang menautkan user ini sebagai viewer.
     * Dicache per-request HTTP untuk mencegah N+1 query pada pemuatan koleksi LPK.
     *
     * @return array<int>
     */
    public function getLinkedOwnerIds(): array
    {
        if ($this->relationLoaded('linkedOwners')) {
            return $this->linkedOwners->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $req = request();
        if ($req) {
            $key = 'req_user_' . $this->id . '_linked_owner_ids';
            if ($req->attributes->has($key)) {
                return (array) $req->attributes->get($key);
            }

            $ids = $this->linkedOwners()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
            $req->attributes->set($key, $ids);

            return $ids;
        }

        return $this->linkedOwners()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
    }

    public function isViewerFor(User|int $owner): bool
    {
        $ownerId = (int) ($owner instanceof User ? $owner->id : $owner);

        if ($this->relationLoaded('linkedOwners')) {
            return $this->linkedOwners->contains('id', $ownerId);
        }

        return in_array($ownerId, $this->getLinkedOwnerIds(), true);
    }

    public function hasLinkedViewer(User|int $viewer): bool
    {
        $viewerId = $viewer instanceof User ? $viewer->id : $viewer;
        return $this->linkedViewers()->where('users.id', $viewerId)->exists();
    }

    public function canManageLpk(Lpk $lpk): bool
    {
        return $lpk->canManage($this);
    }

    /**
     * Daftar akun PIC yang dapat difilter oleh pengguna ini:
     * - Admin: semua PIC dalam sistem.
     * - PIC: akun sendiri ditambah seluruh akun pemilik data (linkedOwners),
     *   akun viewer (linkedViewers), serta PIC dari LPK tempat pengguna menjadi anggota.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    public function getAccessiblePics()
    {
        if ($this->isAdmin()) {
            return static::where('role', static::ROLE_PIC)->orderBy('name')->get();
        }

        $ownerIds = $this->linkedOwners()->pluck('users.id')->all();
        $viewerIds = $this->linkedViewers()->pluck('users.id')->all();
        $memberPicIds = Lpk::whereHas('members', fn ($m) => $m->where('users.id', $this->id))
            ->whereNotNull('pic_id')
            ->pluck('pic_id')
            ->all();

        $allPicIds = array_unique(array_filter(array_merge([$this->id], $ownerIds, $viewerIds, $memberPicIds)));

        return static::whereIn('id', $allPicIds)->orderBy('name')->get();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
