<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Competitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'competitor_url',
        'name',
        'similarity_score',
        'features',
        'tech_stack',
        'traffic_rank',
        'description',
    ];

    protected $casts = [
        'features' => 'array',
        'tech_stack' => 'array',
        'similarity_score' => 'float',
    ];

    /**
     * Get the audit that owns the competitor.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Scope for high similarity competitors
     */
    public function scopeHighSimilarity($query)
    {
        return $query->where('similarity_score', '>=', 0.7);
    }
}
