<?php

namespace App\Models;

use Database\Factories\ServiceCheckFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCheck extends Model
{
    /** @use HasFactory<ServiceCheckFactory> */
    use HasFactory;

    protected $fillable = ['service_id', 'status', 'checked_at', 'message', 'checked_by'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
