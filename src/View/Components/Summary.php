<?php

namespace LaraReviews\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Model;

class Summary extends Component
{
    public array $summary;
    public string $theme;

    public function __construct(
        public Model $reviewable,
        ?string $theme = null
    ) {
        $this->theme = $theme ?? config('larareviews.ui.theme', 'light');
        $this->summary = method_exists($reviewable, 'getAggregatedReviewSummary') 
            ? $reviewable->getAggregatedReviewSummary() 
            : [];
    }

    public function render()
    {
        return view('larareviews::components.summary');
    }
}
