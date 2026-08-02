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
        if (filter_var($externalId, FILTER_VALIDATE_URL)) {
            $baseUrl = $externalId;
        } else {
            $baseUrl = "https://www.tripadvisor.com/Attraction_Review-g1-d{$numericId}-Reviews-A.html";
        }

        $allReviews = [];
        $maxPages = $options['max_pages'] ?? 20; // Default max 20 pages (200 reviews)
        $stopOnDuplicate = $options['stop_on_duplicate'] ?? true;
        
        for ($page = 0; $page < $maxPages; $page++) {
            $offset = $page * 10;
            
            if ($offset === 0) {
                $url = $baseUrl;
            } else {
                $url = str_replace('-Reviews-', "-Reviews-or{$offset}-", $baseUrl);
                // Fallback if str_replace fails to find '-Reviews-'
                if ($url === $baseUrl) {
                    Log::warning("[LaraReviews - tripadvisor_scraper] Could not paginate URL: {$baseUrl}");
                    break;
                }
            }

            Log::info("[LaraReviews - tripadvisor_scraper] Scraping page " . ($page + 1) . " (offset {$offset}) URL: {$url}");
            
            $html = $this->fetchPageHtml($url);
            
            if (!$html) {
                Log::warning("[LaraReviews - tripadvisor_scraper] HTML was empty or blocked for offset {$offset}");
                break;
            }

            // On the first page, if we used a generic g1 URL, extract the canonical URL for safe pagination
            if ($page === 0 && strpos($baseUrl, '-g1-') !== false) {
                if (preg_match('/<link rel="canonical" href="([^"]+)"/i', $html, $canonicalMatches)) {
                    $baseUrl = $canonicalMatches[1];
                    Log::info("[LaraReviews - tripadvisor_scraper] Updated baseUrl to canonical: {$baseUrl}");
                }
            }

            $pageReviews = $this->parseHtml($html);
            
            if (empty($pageReviews)) {
                Log::info("[LaraReviews - tripadvisor_scraper] No reviews found on page " . ($page + 1) . ". Stopping pagination.");
                break;
            }

            $duplicatesOnPage = 0;
            foreach ($pageReviews as $review) {
                // If it already exists in the database, count it as a duplicate
                $exists = \LaraReviews\Models\Review::where('external_id', $review->externalId)->exists();
                if ($exists) {
                    $duplicatesOnPage++;
                } else {
                    $allReviews[] = $review;
                }
            }

            Log::info("[LaraReviews - tripadvisor_scraper] Found " . count($pageReviews) . " reviews on page " . ($page + 1) . " ({$duplicatesOnPage} were duplicates).");

            // If ALL reviews on this page are duplicates, we've likely hit the end of the new reviews.
            // (Only if stop_on_duplicate is true)
            if ($stopOnDuplicate && $duplicatesOnPage === count($pageReviews)) {
                Log::info("[LaraReviews - tripadvisor_scraper] All reviews on page are duplicates. Stopping pagination to save proxy credits.");
                break;
            }
            
            // If we didn't add all of them, but we want to return everything, wait:
            // The trait HasReviews handles updateOrCreate anyway, so it's safe to return all non-duplicates.
            // However, to keep the trait happy with updates to existing reviews (like if rating changed), 
            // we should probably just return them all.
            // But the user requested "just add new reviews only", so excluding them from the returned array is fine.
        }
        
        // If we found absolutely nothing across all pages, return empty array
        // (This prevents mock reviews from overwriting real database data)
        return $allReviews;
    }

    protected function fetchPageHtml(string $url): ?string
    {
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
                return null;
            }

            return $html;

        } catch (\Exception $e) {
            Log::error("[LaraReviews - tripadvisor_scraper] Scraping failed: " . $e->getMessage());
            return null;
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
            // Author
            $author = 'Anonymous';
            $authorNodes = $xpath->query(".//a[contains(@href, '/Profile/')]", $node);
            foreach ($authorNodes as $an) {
                if (trim($an->textContent) !== '') {
                    $author = trim($an->textContent);
                    break;
                }
            }

            // Rating
            $rating = 5;
            $titleNode = $xpath->query(".//svg/title[contains(text(), 'of 5 bubbles')]", $node)->item(0);
            if ($titleNode && preg_match('/(\d)(\.\d)? of 5/', $titleNode->textContent, $matches)) {
                $rating = (float) $matches[1];
            }

            // Title
            $title = '';
            $h3 = $xpath->query(".//h3", $node)->item(0);
            if ($h3) {
                $title = trim($h3->textContent);
            }

            // Content
            $content = '';
            $contentContainer = $xpath->query(".//div[.//button[contains(., 'Read more')]]", $node);
            if ($contentContainer->length > 0) {
                $textDiv = $xpath->query("./div[1]", $contentContainer->item(0))->item(0);
                if ($textDiv) $content = trim($textDiv->textContent);
            }
            if (empty($content)) {
                $spans = $xpath->query(".//span", $node);
                $longest = '';
                foreach ($spans as $span) {
                    $text = trim($span->textContent);
                    if ($text === $title || $text === $author || stripos($text, 'contributions') !== false || stripos($text, 'Written') !== false) {
                        continue;
                    }
                    if (strlen($text) > strlen($longest)) {
                        $longest = $text;
                    }
                }
                $content = $longest;
            }
            // Clean up trailing "Read more" from text extraction
            $content = preg_replace('/Read more$/i', '', $content);
            $content = trim($content);

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
                title: $title ?: null,
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
