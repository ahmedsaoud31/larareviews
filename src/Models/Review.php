<?php

namespace LaraReviews\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'reviewable_type',
        'reviewable_id',
        'review_mapping_id',
        'platform',
        'external_id',
        'reviewer_name',
        'reviewer_avatar',
        'reviewer_location',
        'rating',
        'title',
        'content',
        'review_date',
        'language',
        'original_url',
        'verified',
        'photos',
        'response',
        'raw_data',
    ];

    protected $casts = [
        'rating' => 'float',
        'verified' => 'boolean',
        'photos' => 'array',
        'raw_data' => 'array',
        'review_date' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (Review $review) {
            if ($review->reviewable && method_exists($review->reviewable, 'clearReviewsCache')) {
                $review->reviewable->clearReviewsCache();
            }
        });

        static::deleted(function (Review $review) {
            if ($review->reviewable && method_exists($review->reviewable, 'clearReviewsCache')) {
                $review->reviewable->clearReviewsCache();
            }
        });
    }

    /**
     * Get the owning reviewable model (e.g. Tour).
     */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the associated platform mapping.
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ReviewMapping::class, 'review_mapping_id');
    }

    /**
     * Scope query to specific platform.
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope query to minimum rating.
     */
    public function scopeMinRating($query, float $rating)
    {
        return $query->where('rating', '>=', $rating);
    }
}
