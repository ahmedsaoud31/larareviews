<?php

namespace LaraReviews\DTO;

class ReviewSummaryData
{
    public function __construct(
        public string $platform,
        public float $averageRating = 0.0,
        public int $totalReviews = 0,
        public array $ratingDistribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ],
        public ?string $webUrl = null
    ) {}

    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'average_rating' => round($this->averageRating, 1),
            'total_reviews' => $this->totalReviews,
            'rating_distribution' => $this->ratingDistribution,
            'web_url' => $this->webUrl,
        ];
    }
}
