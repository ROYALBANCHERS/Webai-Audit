<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'match_score',
        'is_notified',
        'notified_at',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    /**
     * Get the job that was matched
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(GovJob::class);
    }

    /**
     * Get the user who was matched
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get unnotified matches
     */
    public function scopeUnnotified($query)
    {
        return $query->where('is_notified', false);
    }

    /**
     * Scope to get notified matches
     */
    public function scopeNotified($query)
    {
        return $query->where('is_notified', true);
    }

    /**
     * Scope to get matches by score
     */
    public function scopeByScore($query, int $minScore = 0)
    {
        return $query->where('match_score', '>=', $minScore);
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): void
    {
        $this->update([
            'is_notified' => true,
            'notified_at' => now()
        ]);
    }
}
