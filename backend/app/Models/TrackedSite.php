<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackedSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'url',
        'name',
        'active',
        'check_interval',
        'last_checked_at',
        'preferences',
    ];

    protected $casts = [
        'active' => 'boolean',
        'check_interval' => 'integer',
        'last_checked_at' => 'datetime',
        'preferences' => 'array',
    ];

    /**
     * Get the user that owns the tracked site.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active sites
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
