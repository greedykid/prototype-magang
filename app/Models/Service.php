<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'system', 'status', 'last_checked_at', 'notes'];

    protected function casts(): array
    {
        return ['last_checked_at' => 'datetime'];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ServiceCheck::class);
    }
}
