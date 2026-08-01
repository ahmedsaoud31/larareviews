<?php

namespace LaraReviews\Contracts;

use LaraReviews\DTO\ReviewData;
use LaraReviews\DTO\ReviewSummaryData;

interface ReviewDriverInterface
{
    /**
     * Get the platform identifier string (e.g., 'tripadvisor', 'viator').
     */
    public function getPlatformName(): string;

    /**
     * Fetch reviews from external platform by entity external ID or URL.
     *
     * @param string $externalId
     * @param array $options
     * @return array<ReviewData>
     */
    public function fetchReviews(string $externalId, array $options = []): array;

    /**
     * Fetch platform summary stats (average rating, review count).
     *
     * @param string $externalId
     * @return ReviewSummaryData|null
     */
    public function fetchSummary(string $externalId): ?ReviewSummaryData;
}
