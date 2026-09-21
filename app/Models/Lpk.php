<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lpk extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'name',
        'scope',
        'certificate_date',
        'address',
        'email',
        'phone',
        'status',
        'notes',
        'expired_at',
        'last_surveillance_notified_at',
        'drive_url',
    ];

    protected function casts(): array
    {
        return [
            'certificate_date' => 'date',
            'expired_at' => 'date',
            'last_surveillance_notified_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function isExpiringSoon(int $days = 90): bool
    {
        if (! $this->expired_at || $this->isExpired()) {
            return false;
        }

        return $this->expired_at->isFuture() && $this->expired_at->lte(now()->addDays($days));
    }

    /**
     * Hitung jadwal acuan siklus pengawasan KAN (S1, S2, dan Re-Akreditasi).
     * S1: Notif bulan 14, kunjungan bulan 15.
     * S2: Notif bulan 35, kunjungan bulan 36.
     * RA: Notif 1 tahun (12 bulan) sebelum masa berlaku sertifikat habis.
     */
    public function getSurveillanceMilestonesAttribute(): array
    {
        $certDate = $this->certificate_date ?: ($this->expired_at ? $this->expired_at->copy()->subYears(5) : null);
        $expDate = $this->expired_at ?: ($certDate ? $certDate->copy()->addYears(5) : null);

        $milestones = [
            's1' => [
                'code' => 'S1',
                'name' => 'Surveilen 1 (S1)',
                'notice_date' => $certDate ? $certDate->copy()->addMonths(14) : null,
                'target_date' => $certDate ? $certDate->copy()->addMonths(15) : null,
                'description' => 'Penilikan pertama (notifikasi bulan ke-14, target kunjungan bulan ke-15)',
                'status' => 'PENDING',
            ],
            's2' => [
                'code' => 'S2',
                'name' => 'Surveilen 2 (S2)',
                'notice_date' => $certDate ? $certDate->copy()->addMonths(35) : null,
                'target_date' => $certDate ? $certDate->copy()->addMonths(36) : null,
                'description' => 'Penilikan kedua (notifikasi bulan ke-35, target kunjungan bulan ke-36)',
                'status' => 'PENDING',
            ],
            'ra' => [
                'code' => 'RA',
                'name' => 'Re-Akreditasi (RA)',
                'notice_date' => $expDate ? $expDate->copy()->subYear() : ($certDate ? $certDate->copy()->addYears(4) : null),
                'target_date' => $expDate,
                'description' => 'Akreditasi ulang (notifikasi 1 tahun sebelum masa berlaku sertifikat habis)',
                'status' => 'PENDING',
            ],
        ];

        if (! $certDate && ! $expDate) {
            return $milestones;
        }

        $now = now();

        // Cek asesmen yang sudah pernah dibuat untuk LPK ini
        $assessments = $this->relationLoaded('assessments') ? $this->assessments : $this->assessments()->get();

        // Cek S1
        if ($milestones['s1']['notice_date']) {
            $hasS1 = $assessments->contains(function ($item) use ($certDate) {
                $isSurv = str_contains(strtolower($item->assessment_type ?: ''), 'survei') || str_contains(strtolower($item->title ?: ''), 's1');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(10)) && $item->start_at->lte($certDate->copy()->addMonths(24));
                return $isSurv && ($isWithinRange || str_contains(strtolower($item->title ?: ''), 's1'));
            });

            if ($hasS1) {
                $milestones['s1']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($now->gte($milestones['s1']['target_date'])) {
                $milestones['s1']['status'] = 'OVERDUE';
            } elseif ($now->gte($milestones['s1']['notice_date'])) {
                $milestones['s1']['status'] = 'DUE';
            } else {
                $milestones['s1']['status'] = 'UPCOMING';
            }
        }

        // Cek S2
        if ($milestones['s2']['notice_date']) {
            $hasS2 = $assessments->contains(function ($item) use ($certDate) {
                $isSurv = str_contains(strtolower($item->assessment_type ?: ''), 'survei') || str_contains(strtolower($item->title ?: ''), 's2');
                $isWithinRange = $item->start_at && $item->start_at->gte($certDate->copy()->addMonths(25)) && $item->start_at->lte($certDate->copy()->addMonths(44));
                return $isSurv && ($isWithinRange || str_contains(strtolower($item->title ?: ''), 's2'));
            });

            if ($hasS2) {
                $milestones['s2']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($now->gte($milestones['s2']['target_date'])) {
                $milestones['s2']['status'] = 'OVERDUE';
            } elseif ($now->gte($milestones['s2']['notice_date'])) {
                $milestones['s2']['status'] = 'DUE';
            } else {
                $milestones['s2']['status'] = 'UPCOMING';
            }
        }

        // Cek RA
        if ($milestones['ra']['notice_date']) {
            $hasRA = $assessments->contains(function ($item) {
                return str_contains(strtolower($item->assessment_type ?: ''), 're-') || str_contains(strtolower($item->title ?: ''), 're-akreditasi') || str_contains(strtolower($item->title ?: ''), 'ra');
            });

            if ($hasRA) {
                $milestones['ra']['status'] = 'COMPLETED_OR_SCHEDULED';
            } elseif ($expDate && $now->gte($expDate)) {
                $milestones['ra']['status'] = 'EXPIRED';
            } elseif ($now->gte($milestones['ra']['notice_date'])) {
                $milestones['ra']['status'] = 'DUE';
            } else {
                $milestones['ra']['status'] = 'UPCOMING';
            }
        }

        return $milestones;
    }

    /**
     * Dapatkan daftar notifikasi persisten yang sedang aktif dan tidak boleh diabaikan.
     */
    public function getActiveSurveillanceAlerts(): array
    {
        $alerts = [];
        $milestones = $this->surveillance_milestones;

        foreach ($milestones as $key => $milestone) {
            if (in_array($milestone['status'], ['DUE', 'OVERDUE', 'EXPIRED'], true)) {
                $isUrgent = in_array($milestone['status'], ['OVERDUE', 'EXPIRED'], true);

                $label = match ($milestone['status']) {
                    'OVERDUE' => 'MELEWATI JADWAL',
                    'EXPIRED' => 'SERTIFIKAT KEDALUWARSA',
                    default => 'WAKTU NOTIFIKASI AKTIF',
                };

                $alerts[] = [
                    'lpk_id' => $this->id,
                    'lpk_reg' => $this->registration_number,
                    'lpk_name' => $this->name,
                    'lpk_email' => $this->email,
                    'code' => $milestone['code'],
                    'name' => $milestone['name'],
                    'notice_date' => $milestone['notice_date'],
                    'target_date' => $milestone['target_date'],
                    'status' => $milestone['status'],
                    'status_label' => $label,
                    'is_urgent' => $isUrgent,
                    'severity' => $isUrgent ? 'danger' : 'warning',
                    'description' => $milestone['description'],
                    'last_notified_at' => $this->last_surveillance_notified_at,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Mengecek apakah LPK memiliki minimal satu notifikasi pengawasan aktif.
     */
    public function hasActiveSurveillanceAlert(): bool
    {
        return count($this->getActiveSurveillanceAlerts()) > 0;
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
