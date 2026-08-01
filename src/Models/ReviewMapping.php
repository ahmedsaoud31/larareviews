<?php

namespace LaraReviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewMapping extends Model
{
    protected $table = 'larareviews_review_mappings';

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'platform',
        'external_id',
        'external_url',
        'settings',
        'last_synced_at',
        'sync_status',
        'error_message',
    ];

    protected $casts = [
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the parent reviewable model (e.g. Tour).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the reviews fetched for this mapping.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'review_mapping_id');
    }

    /**
     * Mark mapping sync as successful.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_status' => 'success',
            'error_message' => null,
        ]);
    }

    /**
     * Mark mapping sync as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
