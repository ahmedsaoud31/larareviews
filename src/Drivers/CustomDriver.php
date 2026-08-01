<?php

namespace LaraReviews\Drivers;

use LaraReviews\DTO\ReviewData;
use LaraReviews\DTO\ReviewSummaryData;

class CustomDriver extends AbstractDriver
{
    public function getPlatformName(): string
    {
        return 'custom';
    }

    public function fetchReviews(string $externalId, array $options = []): array
    {
        // Custom driver returns any custom input or empty array to be managed manually
        return [];
    }

    public function fetchSummary(string $externalId): ?ReviewSummaryData
    {
        return new ReviewSummaryData(platform: 'custom');
    }
}
