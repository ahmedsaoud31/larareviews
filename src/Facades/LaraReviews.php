<?php

namespace LaraReviews\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LaraReviews\Contracts\ReviewDriverInterface driver(string|null $driver = null)
 * @method static array fetchReviews(string $externalId, array $options = [])
 * @method static \LaraReviews\DTO\ReviewSummaryData|null fetchSummary(string $externalId)
 *
 * @see \LaraReviews\LaraReviewsManager
 */
class LaraReviews extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'larareviews';
    }
}
