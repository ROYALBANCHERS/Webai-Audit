<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'overall_score',
        'pages_count',
        'load_time',
        'tech_stack',
        'issues',
        'seo_score',
        'status',
        'error_message',
        'screenshot_path',
        'completed_at',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'issues' => 'array',
        'overall_score' => 'integer',
        'seo_score' => 'integer',
        'pages_count' => 'integer',
        'load_time' => 'float',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the issues for the audit.
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get the tech stacks for the audit.
     */
    public function techStacks(): HasMany
    {
        return $this->hasMany(TechStack::class);
    }

    /**
     * Get the competitors for the audit.
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }

    /**
     * Scope for completed audits
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed audits
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending/running audits
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'running']);
    }
}
