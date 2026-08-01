<?php

namespace LaraReviews\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Model;

class Badge extends Component
{
    public float $averageRating;
    public int $totalReviews;
    public ?string $platformName;

    public function __construct(
        public Model $reviewable,
        public ?string $platform = null
    ) {
        $this->averageRating = method_exists($reviewable, 'getAverageRating')
            ? $reviewable->getAverageRating($platform)
            : 0.0;

        $this->totalReviews = method_exists($reviewable, 'getTotalReviewsCount')
            ? $reviewable->getTotalReviewsCount($platform)
            : 0;

        $this->platformName = $platform 
            ? config("larareviews.ui.platform_names.{$platform}", ucfirst($platform))
            : null;
    }

    public function render()
    {
        return view('larareviews::components.badge');
    }
}
