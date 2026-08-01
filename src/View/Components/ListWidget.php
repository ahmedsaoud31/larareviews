<?php

namespace LaraReviews\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListWidget extends Component
{
    public LengthAwarePaginator|array $reviews;
    public array $summary;
    public string $theme;

    public function __construct(
        public Model $reviewable,
        public ?string $platform = null,
        public int $perPage = 10,
        ?string $theme = null
    ) {
        $this->theme = $theme ?? config('larareviews.ui.theme', 'light');

        $query = method_exists($reviewable, 'reviews')
            ? $reviewable->reviews()
            : null;

        if ($query && $platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        $this->reviews = $query ? $query->paginate($perPage) : [];
        $this->summary = method_exists($reviewable, 'getAggregatedReviewSummary')
            ? $reviewable->getAggregatedReviewSummary()
            : [];
    }

    public function render()
    {
        return view('larareviews::components.list');
    }
}
