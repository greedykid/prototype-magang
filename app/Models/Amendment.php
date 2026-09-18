<?php

namespace App\Models;

use Database\Factories\AmendmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amendment extends Model
{
    /** @use HasFactory<AmendmentFactory> */
    use HasFactory;

    protected $fillable = ['lpk_id', 'created_by', 'submission_number', 'amendment_type', 'submitted_at', 'status', 'target_date', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['submitted_at' => 'date', 'target_date' => 'date', 'completed_at' => 'date'];
    }

    public function lpk(): BelongsTo
    {
        return $this->belongsTo(Lpk::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
