<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'accreditation_id',
        'sk_number',
        'signer_name',
        'signer_title',
        'signer_nip',
        'is_signed',
        'signed_at',
        'certificate_series',
        'verify_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function accreditation(): BelongsTo
    {
        return $this->belongsTo(Accreditation::class);
    }

    public static function generateHash(int $accreditationId, string $skNumber): string
    {
        return hash('sha256', "KAN-BSN:{$accreditationId}:{$skNumber}:" . now()->toIso8601String() . ':' . config('app.key'));
    }
}
