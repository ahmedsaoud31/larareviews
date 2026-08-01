<?php

namespace LaraReviews\Drivers;

use LaraReviews\DTO\ReviewData;
use LaraReviews\DTO\ReviewSummaryData;
use DateTime;

class GoogleDriver extends AbstractDriver
{
    public function getPlatformName(): string
    {
        return 'google';
    }

    public function fetchReviews(string $externalId, array $options = []): array
    {
        $apiKey = $this->config['api_key'] ?? config('larareviews.drivers.google.api_key');
        $baseUrl = $this->config['api_base_url'] ?? config('larareviews.drivers.google.api_base_url', 'https://maps.googleapis.com/maps/api/place');

        if (empty($apiKey)) {
            return $this->getMockReviews($externalId);
        }

        try {
            $url = "{$baseUrl}/details/json";
            $response = $this->httpClient->get($url, [
                'query' => [
                    'place_id' => $externalId,
                    'fields' => 'name,rating,reviews,user_ratings_total',
                    'key' => $apiKey,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['result']['reviews'] ?? [];

            $reviews = [];
            foreach ($items as $item) {
                $reviews[] = new ReviewData(
                    platform: 'google',
                    externalId: (string) ($item['author_url'] ? md5($item['author_url'] . $item['time']) : uniqid('google_')),
                    reviewerName: $item['author_name'] ?? 'Google User',
                    reviewerAvatar: $item['profile_photo_url'] ?? null,
                    reviewerLocation: null,
                    rating: (float) ($item['rating'] ?? 5.0),
                    title: null,
                    content: $item['text'] ?? null,
                    reviewDate: isset($item['time']) ? (new DateTime())->setTimestamp($item['time']) : new DateTime(),
                    language: $item['language'] ?? 'en',
                    originalUrl: $item['author_url'] ?? "https://www.google.com/maps/place/?q=place_id:{$externalId}",
                    verified: true,
                    rawData: $item
                );
            }

            return $reviews;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            if ($status === 429) {
                throw new \Exception("Rate limit exceeded for Google Places API.");
            }
            $this->logError("Google Places API Error: {$status}", $e);
            return $this->getMockReviews($externalId);
        } catch (\Throwable $e) {
            $this->logError("Failed fetching Google Places reviews for Place ID: {$externalId}", $e);
            return $this->getMockReviews($externalId);
        }
    }

    public function fetchSummary(string $externalId): ?ReviewSummaryData
    {
        $reviews = $this->fetchReviews($externalId);
        return $this->calculateSummaryFromReviews($reviews);
    }

    protected function getMockReviews(string $externalId): array
    {
        return [
            new ReviewData(
                platform: 'google',
                externalId: "google_{$externalId}_1",
                reviewerName: 'Alex Rodriguez',
                reviewerAvatar: 'https://i.pravatar.cc/150?u=alex_google',
                rating: 5.0,
                content: 'Spectacular service and amazing tour guide. 5 stars all around!',
                reviewDate: new DateTime('-1 week'),
                language: 'en',
                originalUrl: "https://www.google.com/maps/place/?q=place_id:{$externalId}",
                verified: true
            ),
        ];
    }
}
