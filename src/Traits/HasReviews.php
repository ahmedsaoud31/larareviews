<?php

namespace LaraReviews\Traits;

use LaraReviews\Models\Review;
use LaraReviews\Models\ReviewMapping;
use LaraReviews\Facades\LaraReviews;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Cache;

trait HasReviews
{
    /**
     * Get all collected reviews for this model.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->orderByDesc('review_date');
    }

    /**
     * Get all connected platform review mappings for this model.
     */
    public function reviewMappings(): MorphMany
    {
        return $this->morphMany(ReviewMapping::class, 'reviewable');
    }

    /**
     * Connect or update a review platform for this model.
     *
     * Example: $tour->connectReviewPlatform('tripadvisor', '12345', 'https://tripadvisor.com/...');
     */
    public function connectReviewPlatform(
        string $platform,
        string $externalId,
        ?string $externalUrl = null,
        array $settings = []
    ): ReviewMapping {
        $platform = strtolower(trim($platform));

        $mapping = $this->reviewMappings()->updateOrCreate(
            ['platform' => $platform],
            [
                'external_id' => $externalId,
                'external_url' => $externalUrl,
                'settings' => $settings,
                'sync_status' => 'pending',
            ]
        );

        // Optionally trigger immediate sync or dispatch job
        $this->syncReviews($platform);

        return $mapping;
    }

    /**
     * Disconnect a review platform from this model and delete stored reviews.
     */
    public function disconnectReviewPlatform(string $platform): bool
    {
        $platform = strtolower(trim($platform));

        $mapping = $this->reviewMappings()->where('platform', $platform)->first();

        if ($mapping) {
            $this->reviews()->where('platform', $platform)->delete();
            $mapping->delete();
            $this->clearReviewsCache();
            return true;
        }

        return false;
    }

    /**
     * Sync reviews from external platform(s) for this model.
     */
    public function syncReviews(?string $platform = null): array
    {
        $mappingsQuery = $this->reviewMappings();

        if ($platform) {
            $mappingsQuery->where('platform', strtolower(trim($platform)));
        }

        $mappings = $mappingsQuery->get();
        $syncedCount = 0;
        $errors = [];

        foreach ($mappings as $mapping) {
            try {
                $driver = LaraReviews::driver($mapping->platform);
                $reviewsData = $driver->fetchReviews($mapping->external_id, $mapping->settings ?? []);

                foreach ($reviewsData as $data) {
                    $this->reviews()->updateOrCreate(
                        [
                            'platform' => $data->platform,
                            'external_id' => $data->externalId,
                        ],
                        array_merge($data->toArray(), [
                            'review_mapping_id' => $mapping->id,
                        ])
                    );
                    $syncedCount++;
                }

                $mapping->markAsSynced();
            } catch (\Throwable $e) {
                $mapping->markAsFailed($e->getMessage());
                $errors[$mapping->platform] = $e->getMessage();
            }
        }

        $this->clearReviewsCache();

        return [
            'synced_reviews' => $syncedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Get the overall average rating score.
     */
    public function getAverageRating(?string $platform = null): float
    {
        $query = $this->reviews();
        if ($platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        $avg = $query->avg('rating');

        return $avg ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Get total review count.
     */
    public function getTotalReviewsCount(?string $platform = null): int
    {
        $query = $this->reviews();
        if ($platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        return $query->count();
    }

    /**
     * Get rating score distribution (5-star, 4-star, 3-star, 2-star, 1-star).
     */
    public function getRatingBreakdown(?string $platform = null): array
    {
        $query = $this->reviews();
        if ($platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        $reviews = $query->get(['rating']);
        $total = $reviews->count();

        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        foreach ($reviews as $review) {
            $star = (int) round($review->rating);
            $star = max(1, min(5, $star));
            $distribution[$star]++;
        }

        $percentage = [];
        foreach ($distribution as $star => $count) {
            $percentage[$star] = $total > 0 ? round(($count / $total) * 100) : 0;
        }

        return [
            'counts' => $distribution,
            'percentages' => $percentage,
            'total' => $total,
        ];
    }

    /**
     * Get per-platform summary breakdown (e.g. TripAdvisor score, Viator score).
     */
    public function getPlatformSummaries(): array
    {
        $summaries = [];
        $mappings = $this->reviewMappings()->get();

        foreach ($mappings as $mapping) {
            $platform = $mapping->platform;
            $avg = $this->getAverageRating($platform);
            $count = $this->getTotalReviewsCount($platform);

            $summaries[$platform] = [
                'platform' => $platform,
                'name' => config("larareviews.ui.platform_names.{$platform}", ucfirst($platform)),
                'color' => config("larareviews.ui.platform_colors.{$platform}", '#6C757D'),
                'external_id' => $mapping->external_id,
                'external_url' => $mapping->external_url,
                'average_rating' => $avg,
                'total_reviews' => $count,
                'last_synced_at' => $mapping->last_synced_at,
                'sync_status' => $mapping->sync_status,
            ];
        }

        return $summaries;
    }

    /**
     * Get consolidated summary data object.
     */
    public function getAggregatedReviewSummary(): array
    {
        $cacheKey = "larareviews_summary_" . str_replace('\\', '_', $this->getMorphClass()) . "_{$this->getKey()}";

        if (!config('larareviews.cache.enabled', true)) {
            return $this->buildAggregatedReviewSummary();
        }

        return Cache::remember(
            $cacheKey,
            config('larareviews.cache.ttl', 86400),
            fn() => $this->buildAggregatedReviewSummary()
        );
    }

    protected function buildAggregatedReviewSummary(): array
    {
        return [
            'average_rating' => $this->getAverageRating(),
            'total_reviews' => $this->getTotalReviewsCount(),
            'breakdown' => $this->getRatingBreakdown(),
            'platforms' => $this->getPlatformSummaries(),
        ];
    }

    /**
     * Clear cached review summary for this model instance.
     */
    public function clearReviewsCache(): void
    {
        $cacheKey = "larareviews_summary_" . str_replace('\\', '_', $this->getMorphClass()) . "_{$this->getKey()}";
        Cache::forget($cacheKey);
    }
}
