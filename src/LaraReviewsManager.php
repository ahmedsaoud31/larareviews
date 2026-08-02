<?php

namespace LaraReviews;

use LaraReviews\Contracts\ReviewDriverInterface;
use LaraReviews\Drivers\TripAdvisorDriver;
use LaraReviews\Drivers\ViatorDriver;
use LaraReviews\Drivers\GetYourGuideDriver;
use LaraReviews\Drivers\GoogleDriver;
use LaraReviews\Drivers\CustomDriver;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class LaraReviewsManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->container['config']['larareviews.default'] ?? 'tripadvisor';
    }

    /**
     * Create TripAdvisor driver instance.
     */
    protected function createTripadvisorDriver(): ReviewDriverInterface
    {
        $config = $this->container['config']['larareviews.drivers.tripadvisor_scraper'] ?? [];
        // Use the Scraper driver by default for 'tripadvisor' to force scraping during sync
        return new \LaraReviews\Drivers\TripAdvisorScraperDriver($config);
    }

    /**
     * Create Viator driver instance.
     */
    protected function createViatorDriver(): ReviewDriverInterface
    {
        $config = $this->container['config']['larareviews.drivers.viator'] ?? [];
        return new ViatorDriver($config);
    }

    /**
     * Create GetYourGuide driver instance.
     */
    protected function createGetyourguideDriver(): ReviewDriverInterface
    {
        $config = $this->container['config']['larareviews.drivers.getyourguide'] ?? [];
        return new GetYourGuideDriver($config);
    }

    /**
     * Create Google driver instance.
     */
    protected function createGoogleDriver(): ReviewDriverInterface
    {
        $config = $this->container['config']['larareviews.drivers.google'] ?? [];
        return new GoogleDriver($config);
    }

    /**
     * Create Custom driver instance.
     */
    protected function createCustomDriver(): ReviewDriverInterface
    {
        $config = $this->container['config']['larareviews.drivers.custom'] ?? [];
        return new CustomDriver($config);
    }

    /**
     * Dynamically resolve custom registered or configured drivers.
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (InvalidArgumentException $e) {
            $configuredClass = $this->container['config']["larareviews.drivers.{$driver}.class"] ?? null;
            if ($configuredClass && class_exists($configuredClass)) {
                $config = $this->container['config']["larareviews.drivers.{$driver}"] ?? [];
                return new $configuredClass($config);
            }
            throw $e;
        }
    }
}
