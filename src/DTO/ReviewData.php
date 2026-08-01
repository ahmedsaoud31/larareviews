<?php

namespace LaraReviews\DTO;

use DateTimeInterface;

class ReviewData
{
    public function __construct(
        public string $platform,
        public string $externalId,
        public ?string $reviewerName = null,
        public ?string $reviewerAvatar = null,
        public ?string $reviewerLocation = null,
        public float $rating = 5.0,
        public ?string $title = null,
        public ?string $content = null,
        public ?DateTimeInterface $reviewDate = null,
        public string $language = 'en',
        public ?string $originalUrl = null,
        public bool $verified = true,
        public array $photos = [],
        public ?string $response = null,
        public array $rawData = []
    ) {}

    public function toArray(): array
    {
        return [
            'platform' => $this->platform,
            'external_id' => $this->externalId,
            'reviewer_name' => $this->reviewerName,
            'reviewer_avatar' => $this->reviewerAvatar,
            'reviewer_location' => $this->reviewerLocation,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'review_date' => $this->reviewDate?->format('Y-m-d H:i:s'),
            'language' => $this->language,
            'original_url' => $this->originalUrl,
            'verified' => $this->verified,
            'photos' => json_encode($this->photos),
            'response' => $this->response,
            'raw_data' => json_encode($this->rawData),
        ];
    }
}
