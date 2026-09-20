<?php

namespace App\Models;

use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    /**
     * 6 Skema Pelaksanaan Asesmen Resmi sesuai Dokumen KAN U-01 Rev.3
     */
    public const TYPES = [
        'Asesmen Awal' => 'Asesmen Awal (Initial Assessment)',
        'Surveilen' => 'Surveilen (Surveillance)',
        'Re-asesmen' => 'Re-asesmen (Reassessment)',
        'Audit Kecukupan' => 'Audit Kecukupan (Adequacy Audit)',
        'Penyaksian (Witness)' => 'Penyaksian (Witnessing)',
        'Perluasan Lingkup' => 'Perluasan Lingkup (Scope Extension)',
    ];

    protected $fillable = ['lpk_id', 'created_by', 'title', 'assessment_type', 'start_at', 'end_at', 'location', 'status', 'lead_assessor', 'notes'];

    protected function casts(): array
    {
        return ['start_at' => 'datetime', 'end_at' => 'datetime'];
    }

    public function getAssessmentTypeLabelAttribute(): string
    {
        return match ($this->assessment_type) {
            'INITIAL', 'Asesmen Awal' => 'Asesmen Awal (Initial)',
            'SURVEILLANCE', 'Surveilen' => 'Surveilen (Surveillance)',
            'REASSESSMENT', 'Re-asesmen' => 'Re-asesmen (Reassessment)',
            'Audit Kecukupan', 'ADEQUACY' => 'Audit Kecukupan (Adequacy)',
            'Penyaksian (Witness)', 'Penyaksian', 'WITNESS' => 'Penyaksian (Witness)',
            'Perluasan Lingkup', 'SCOPE_EXTENSION' => 'Perluasan Lingkup',
            default => $this->assessment_type ?: '-',
        };
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expense(): HasOne
    {
        return $this->hasOne(AssessmentExpense::class);
    }
}
