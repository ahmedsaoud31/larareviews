<?php

namespace LaraReviews\Drivers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TripAdvisorScraperDriver extends AbstractDriver
{
    public function fetchReviews(string $externalId): array
    {
        // Try to extract the numeric ID if externalId contains "ta_" or similar
        if (preg_match('/(\d+)/', $externalId, $matches)) {
            $numericId = $matches[1];
        } else {
            $numericId = $externalId;
        }

        // Construct a generic TripAdvisor URL. 
        // TripAdvisor usually redirects to the correct slug if the location ID is correct.
        if (filter_var($externalId, FILTER_VALIDATE_URL)) {
            $url = $externalId;
        } else {
            // Using g1 as a generic geo ID (Tripadvisor often auto-redirects)
            $url = "https://www.tripadvisor.com/Attraction_Review-g1-d{$numericId}-Reviews-A.html";
        }

        Log::info("[LaraReviews - tripadvisor_scraper] Starting scrape for URL: {$url}");

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                    'Sec-Ch-Ua' => '"Not A(Brand";v="99", "Google Chrome";v="121", "Chromium";v="121"',
                    'Sec-Ch-Ua-Mobile' => '?0',
                    'Sec-Ch-Ua-Platform' => '"Windows"',
                    'Sec-Fetch-Dest' => 'document',
                    'Sec-Fetch-Mode' => 'navigate',
                    'Sec-Fetch-Site' => 'none',
                    'Sec-Fetch-User' => '?1',
                    'Upgrade-Insecure-Requests' => '1',
                ],
                // Don't throw exceptions on 403 so we can parse the DataDome response
                'http_errors' => false,
            ]);

            $html = $response->getBody()->getContents();
            $statusCode = $response->getStatusCode();

            // Very basic fallback if DataDome blocks us (usually 403 or 429)
            if ($statusCode !== 200 || strpos($html, 'datadome') !== false || strpos($html, 'Cloudflare') !== false) {
                Log::warning("[LaraReviews - tripadvisor_scraper] Blocked by DataDome/Cloudflare (Status: $statusCode) on URL: {$url}");
                return $this->getMockReviews($externalId);
            }

            return $this->parseHtml($html);

        } catch (\Exception $e) {
            Log::error("[LaraReviews - tripadvisor_scraper] Scraping failed: " . $e->getMessage());
            // Fallback to mock for development continuity
            return $this->getMockReviews($externalId);
        }
    }

    protected function parseHtml(string $html): array
    {
        $reviews = [];
        
        // Suppress libxml warnings for malformed HTML
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOBLANKS | LIBXML_COMPACT);
        $xpath = new \DOMXPath($dom);

        // This is a naive selector, TripAdvisor's DOM changes very frequently.
        // Current typical review container might contain data-test-target="HR_CC_CARD" or "review-container"
        $nodes = $xpath->query("//div[@data-test-target='HR_CC_CARD'] | //div[contains(@class, 'review-container')] | //div[contains(@data-automation, 'reviewCard')]");
        
        if ($nodes->length === 0) {
            Log::warning("[LaraReviews - tripadvisor_scraper] No reviews found in HTML. Selectors might be outdated or page is rendered via React hydrate without SSR.");
            return $this->getMockReviews('no_reviews_found');
        }

        foreach ($nodes as $node) {
            $author = 'Anonymous';
            $authorNode = $xpath->query(".//a[contains(@class, 'ui_header_link')] | .//span[contains(@class, 'ui_header_link')] | .//div[contains(@class, 'info_text')]/div", $node)->item(0);
            if ($authorNode) {
                $author = trim($authorNode->textContent);
            }

            $content = '';
            $contentNode = $xpath->query(".//q/span | .//span[contains(@class, 'partial_entry')] | .//span[@data-automation='reviewText']", $node)->item(0);
            if ($contentNode) {
                $content = trim($contentNode->textContent);
            }

            $rating = 5;
            $ratingNode = $xpath->query(".//span[contains(@class, 'ui_bubble_rating')] | .//svg[contains(@class, 'Uctuv')]", $node)->item(0);
            if ($ratingNode) {
                $class = $ratingNode->getAttribute('class');
                $aria = $ratingNode->getAttribute('aria-label');
                if (preg_match('/bubble_(\d)0/', $class, $matches)) {
                    $rating = (int) $matches[1];
                } elseif (preg_match('/(\d)(\.\d)? of 5/', $aria, $matches)) {
                    $rating = (int) $matches[1];
                }
            }

            // Skip empty contents to avoid junk
            if (empty($content)) {
                continue;
            }

            $reviews[] = [
                'external_id' => 'ta_scrape_' . md5($author . $content),
                'author_name' => $author,
                'author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($author),
                'rating' => $rating,
                'content' => $content,
                'url' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
            ];
        }

        return $reviews;
    }

    protected function getMockReviews(string $externalId): array
    {
        return [
            [
                'external_id' => 'ta_scrape_mock_1',
                'author_name' => 'Scrape Mock User 1',
                'author_avatar' => 'https://ui-avatars.com/api/?name=Scrape+Mock+1',
                'rating' => 5,
                'content' => 'This is a mocked scraped review because the scraper was blocked by DataDome/Cloudflare, or the DOM selectors are outdated.',
                'url' => 'https://www.tripadvisor.com',
                'created_at' => Carbon::now()->subDays(1)->toDateTimeString(),
            ],
            [
                'external_id' => 'ta_scrape_mock_2',
                'author_name' => 'Scrape Mock User 2',
                'author_avatar' => 'https://ui-avatars.com/api/?name=Scrape+Mock+2',
                'rating' => 4,
                'content' => 'Another mock scraped review. The scraper needs rotating residential proxies to bypass bot protection reliably.',
                'url' => 'https://www.tripadvisor.com',
                'created_at' => Carbon::now()->subDays(2)->toDateTimeString(),
            ],
            [
                'external_id' => 'ta_scrape_mock_3',
                'author_name' => 'Scrape Mock User 3',
                'author_avatar' => 'https://ui-avatars.com/api/?name=Scrape+Mock+3',
                'rating' => 5,
                'content' => 'You can change the driver back to tripadvisor to use the API again.',
                'url' => 'https://www.tripadvisor.com',
                'created_at' => Carbon::now()->subDays(3)->toDateTimeString(),
            ]
        ];
    }
}
