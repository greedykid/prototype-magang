<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accreditation extends Model
{
    use HasFactory;

    protected $fillable = ['lpk_id', 'status', 'start_date', 'pantek_at', 'target_date', 'target_output_at', 'output_released_at', 'pic', 'notes'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'pantek_at' => 'date', 'target_date' => 'date', 'target_output_at' => 'date', 'output_released_at' => 'date'];
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function isReleaseReady(): bool
    {
        if ($this->status === 'COMPLETED' || ! empty($this->output_released_at)) {
            return true;
        }

        $assessments = $this->lpk?->assessments;
        if (! $assessments || $assessments->isEmpty()) {
            return false;
        }

        $hasOverdueTp = $assessments->contains(fn ($asm) => $asm->is_tp_overdue);
        $allCompleted = $assessments->every(fn ($asm) => $asm->status === 'COMPLETED');

        return $allCompleted && ! $hasOverdueTp;
    }
}
