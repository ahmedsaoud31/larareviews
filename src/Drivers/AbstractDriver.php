<?php

namespace LaraReviews\Drivers;

use LaraReviews\Contracts\ReviewDriverInterface;
use LaraReviews\DTO\ReviewData;
use LaraReviews\DTO\ReviewSummaryData;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

abstract class AbstractDriver implements ReviewDriverInterface
{
    protected Client $httpClient;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 15,
            'headers' => [
                'User-Agent' => 'LaraReviews-Agent/1.0',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get platform name.
     */
    abstract public function getPlatformName(): string;

    /**
     * Helper to log driver warnings/errors.
     */
    protected function logError(string $message, \Throwable $exception = null): void
    {
        Log::warning("[LaraReviews - {$this->getPlatformName()}] {$message}", [
            'exception' => $exception?->getMessage(),
        ]);
    }

    /**
     * Fallback summary calculation from array of ReviewData DTOs.
     */
    protected function calculateSummaryFromReviews(array $reviews): ReviewSummaryData
    {
        if (empty($reviews)) {
            return new ReviewSummaryData(platform: $this->getPlatformName());
        }

        $total = count($reviews);
        $sum = 0.0;
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        foreach ($reviews as $review) {
            $sum += $review->rating;
            $star = (int) round($review->rating);
            $star = max(1, min(5, $star));
            $distribution[$star]++;
        }

        return new ReviewSummaryData(
            platform: $this->getPlatformName(),
            averageRating: round($sum / $total, 1),
            totalReviews: $total,
            ratingDistribution: $distribution
        );
    }
}
