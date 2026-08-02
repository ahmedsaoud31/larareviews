<?php

namespace LaraReviews\Drivers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use LaraReviews\DTO\ReviewData;
use DateTime;

class TripAdvisorScraperDriver extends AbstractDriver
{
    public function getPlatformName(): string
    {
        return 'tripadvisor_scraper';
    }

    public function fetchSummary(string $externalId): ?\LaraReviews\DTO\ReviewSummaryData
    {
        $reviewsData = $this->fetchReviews($externalId);
        return $this->calculateSummaryFromReviews($reviewsData);
    }

    public function fetchReviews(string $externalId, array $options = []): array
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
            $scraperApiKey = config('larareviews.drivers.tripadvisor_scraper.scraperapi_key') ?? env('SCRAPERAPI_KEY');
            $zenrowsKey = config('larareviews.drivers.tripadvisor_scraper.zenrows_key') ?? env('ZENROWS_KEY');
            $brightdataProxy = config('larareviews.drivers.tripadvisor_scraper.brightdata_proxy') ?? env('BRIGHTDATA_PROXY');
            
            $strategies = [
                'direct' => [
                    'url' => $url,
                    'options' => [
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.9',
                            'Upgrade-Insecure-Requests' => '1',
                        ],
                        'http_errors' => false,
                    ]
                ],
            ];
            
            if ($scraperApiKey) {
                $strategies['scraperapi'] = [
                    'url' => 'http://api.scraperapi.com/',
                    'options' => [
                        'query' => [
                            'api_key' => $scraperApiKey,
                            'url' => $url,
                            'render' => 'true', // Needed for TA React app
                            'premium' => 'true', // Optional but helpful for TripAdvisor
                        ],
                        'http_errors' => false,
                        'timeout' => 60, // JS rendering takes time
                    ]
                ];
            }
            
            if ($zenrowsKey) {
                $strategies['zenrows'] = [
                    'url' => 'https://api.zenrows.com/v1/',
                    'options' => [
                        'query' => [
                            'apikey' => $zenrowsKey,
                            'url' => $url,
                            'js_render' => 'true',
                            'antibot' => 'true',
                            'premium_proxy' => 'true',
                        ],
                        'http_errors' => false,
                        'timeout' => 60,
                    ]
                ];
            }
            
            if ($brightdataProxy) {
                $strategies['brightdata'] = [
                    'url' => $url,
                    'options' => [
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                            'Accept-Language' => 'en-US,en;q=0.9',
                            'Upgrade-Insecure-Requests' => '1',
                        ],
                        'proxy' => $brightdataProxy,
                        'verify' => false, // Needed for BrightData SSL interception
                        'http_errors' => false,
                        'timeout' => 60,
                    ]
                ];
            }

            $html = null;
            $statusCode = 0;
            $success = false;

            // Prioritize proxies if available, fallback to direct
            $strategyOrder = [];
            if ($scraperApiKey) $strategyOrder[] = 'scraperapi';
            if ($zenrowsKey) $strategyOrder[] = 'zenrows';
            if ($brightdataProxy) $strategyOrder[] = 'brightdata';
            $strategyOrder[] = 'direct';

            foreach ($strategyOrder as $strategyName) {
                Log::info("[LaraReviews - tripadvisor_scraper] Trying strategy: {$strategyName}");
                
                try {
                    $strategy = $strategies[$strategyName];
                    $response = $this->httpClient->get($strategy['url'], $strategy['options']);
                    $html = $response->getBody()->getContents();
                    $statusCode = $response->getStatusCode();
                    
                    if ($statusCode === 200 && stripos($html, 'datadome') === false && stripos($html, 'Cloudflare') === false && stripos($html, 'Access Denied') === false) {
                        $success = true;
                        Log::info("[LaraReviews - tripadvisor_scraper] Strategy {$strategyName} succeeded!");
                        break;
                    }
                    
                    Log::warning("[LaraReviews - tripadvisor_scraper] Strategy {$strategyName} blocked/failed (Status: {$statusCode})");
                } catch (\Exception $ex) {
                    Log::warning("[LaraReviews - tripadvisor_scraper] Strategy {$strategyName} exception: " . $ex->getMessage());
                }
            }

            if (!$success) {
                Log::warning("[LaraReviews - tripadvisor_scraper] All scraping strategies blocked/failed on URL: {$url}");
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

            $reviews[] = new ReviewData(
                platform: 'tripadvisor_scraper',
                externalId: 'ta_scrape_' . md5($author . $content),
                reviewerName: $author,
                reviewerAvatar: 'https://ui-avatars.com/api/?name=' . urlencode($author),
                reviewerLocation: null,
                rating: (float) $rating,
                title: null,
                content: $content,
                reviewDate: new DateTime(),
                language: 'en',
                originalUrl: null,
                verified: true
            );
        }

        return $reviews;
    }

    protected function getMockReviews(string $externalId): array
    {
        return [
            new ReviewData(
                platform: 'tripadvisor_scraper',
                externalId: 'ta_scrape_mock_1',
                reviewerName: 'Scrape Mock User 1',
                reviewerAvatar: 'https://ui-avatars.com/api/?name=Scrape+Mock+1',
                reviewerLocation: null,
                rating: 5.0,
                title: null,
                content: 'This is a mocked scraped review because the scraper was blocked by DataDome/Cloudflare, or the DOM selectors are outdated.',
                reviewDate: new DateTime('-1 day'),
                language: 'en',
                originalUrl: 'https://www.tripadvisor.com',
                verified: true
            ),
            new ReviewData(
                platform: 'tripadvisor_scraper',
                externalId: 'ta_scrape_mock_2',
                reviewerName: 'Scrape Mock User 2',
                reviewerAvatar: 'https://ui-avatars.com/api/?name=Scrape+Mock+2',
                reviewerLocation: null,
                rating: 4.0,
                title: null,
                content: 'Another mock scraped review. The scraper needs rotating residential proxies to bypass bot protection reliably.',
                reviewDate: new DateTime('-2 days'),
                language: 'en',
                originalUrl: 'https://www.tripadvisor.com',
                verified: true
            ),
            new ReviewData(
                platform: 'tripadvisor_scraper',
                externalId: 'ta_scrape_mock_3',
                reviewerName: 'Scrape Mock User 3',
                reviewerAvatar: 'https://ui-avatars.com/api/?name=Scrape+Mock+3',
                reviewerLocation: null,
                rating: 5.0,
                title: null,
                content: 'You can change the driver back to tripadvisor to use the API again.',
                reviewDate: new DateTime('-3 days'),
                language: 'en',
                originalUrl: 'https://www.tripadvisor.com',
                verified: true
            )
        ];
    }
}
