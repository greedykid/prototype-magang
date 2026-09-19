<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'accreditation_id',
        'billing_code',
        'tariff_name',
        'amount',
        'issued_at',
        'expired_at',
        'status',
        'ntpn',
        'ntb',
        'payment_channel',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'issued_at' => 'datetime',
            'expired_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function accreditation(): BelongsTo
    {
        return $this->belongsTo(Accreditation::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }

    public static function generateSimponiCode(): string
    {
        // SIMPONI 15-digit code standard (starts with 8 + 14 random digits)
        return '8' . str_pad((string) random_int(10000000000000, 99999999999999), 14, '0', STR_PAD_LEFT);
    }

    public static function generateNtpn(): string
    {
        // 16-character alphanumeric uppercase NTPN (Nomor Transaksi Penerimaan Negara)
        return strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
    }
}
