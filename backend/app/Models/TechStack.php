<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechStack extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'category',
        'name',
        'version',
        'confidence',
        'detection_data',
    ];

    protected $casts = [
        'detection_data' => 'array',
    ];

    /**
     * Get the audit that owns the tech stack entry.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for high confidence detections
     */
    public function scopeHighConfidence($query)
    {
        return $query->where('confidence', 'high');
    }
}
