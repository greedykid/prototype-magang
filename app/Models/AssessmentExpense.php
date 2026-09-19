<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'reported_by',
        'transport_cost',
        'accommodation_cost',
        'daily_allowance',
        'package_data_cost',
        'total_cost',
        'receipt_note',
        'receipt_path',
        'status',
        'verification_notes',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'transport_cost' => 'integer',
            'accommodation_cost' => 'integer',
            'daily_allowance' => 'integer',
            'package_data_cost' => 'integer',
            'total_cost' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->status === 'TERVERIFIKASI';
    }

    public function recalculateTotal(): int
    {
        $this->total_cost = (int) $this->transport_cost +
            (int) $this->accommodation_cost +
            (int) $this->daily_allowance +
            (int) $this->package_data_cost;

        return $this->total_cost;
    }
}
