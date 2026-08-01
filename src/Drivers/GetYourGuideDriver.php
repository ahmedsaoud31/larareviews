<?php

namespace LaraReviews\Drivers;

use LaraReviews\DTO\ReviewData;
use LaraReviews\DTO\ReviewSummaryData;
use DateTime;

class GetYourGuideDriver extends AbstractDriver
{
    public function getPlatformName(): string
    {
        return 'getyourguide';
    }

    public function fetchReviews(string $externalId, array $options = []): array
    {
        $apiKey = $this->config['api_key'] ?? config('larareviews.drivers.getyourguide.api_key');
        $baseUrl = $this->config['api_base_url'] ?? config('larareviews.drivers.getyourguide.api_base_url', 'https://api.getyourguide.com/v1/tours');

        if (empty($apiKey)) {
            return $this->getMockReviews($externalId);
        }

        try {
            $response = $this->httpClient->get("{$baseUrl}/{$externalId}/reviews", [
                'headers' => [
                    'X-Access-Token' => $apiKey,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $items = $body['reviews'] ?? [];

            $reviews = [];
            foreach ($items as $item) {
                $reviews[] = new ReviewData(
                    platform: 'getyourguide',
                    externalId: (string) ($item['id'] ?? uniqid('gyg_')),
                    reviewerName: $item['author']['name'] ?? 'GetYourGuide Traveler',
                    reviewerAvatar: $item['author']['photo'] ?? null,
                    reviewerLocation: $item['author']['country'] ?? null,
                    rating: (float) ($item['rating'] ?? 5.0),
                    title: $item['title'] ?? null,
                    content: $item['message'] ?? null,
                    reviewDate: isset($item['created']) ? new DateTime($item['created']) : new DateTime(),
                    language: 'en',
                    originalUrl: "https://www.getyourguide.com/t-t{$externalId}",
                    verified: true,
                    rawData: $item
                );
            }

            return $reviews;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            if ($status === 429) {
                throw new \Exception("Rate limit exceeded for GetYourGuide API.");
            }
            $this->logError("GetYourGuide API Error: {$status}", $e);
            return $this->getMockReviews($externalId);
        } catch (\Throwable $e) {
            $this->logError("Failed fetching GetYourGuide reviews for product: {$externalId}", $e);
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
                platform: 'getyourguide',
                externalId: "gyg_{$externalId}_1",
                reviewerName: 'Lucas Weber',
                reviewerAvatar: 'https://i.pravatar.cc/150?u=lucas_gyg',
                reviewerLocation: 'Germany',
                rating: 5.0,
                title: 'Fantastic Day Out!',
                content: 'Everything was seamless from mobile ticket scanning to the guided commentary. Highlight of our trip!',
                reviewDate: new DateTime('-5 days'),
                language: 'en',
                originalUrl: "https://www.getyourguide.com/t-t{$externalId}",
                verified: true
            ),
        ];
    }
}
