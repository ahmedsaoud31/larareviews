<?php

namespace LaraReviews\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Model;

class Grid extends Component
{
    public $reviews;
    public string $theme;

    public function __construct(
        public Model $reviewable,
        public ?string $platform = null,
        public int $limit = 6,
        public int $cols = 3,
        ?string $theme = null
    ) {
        $this->theme = $theme ?? config('larareviews.ui.theme', 'light');

        $query = method_exists($reviewable, 'reviews')
            ? $reviewable->reviews()
            : null;

        if ($query && $platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        $this->reviews = $query ? $query->limit($limit)->get() : collect();
    }

    public function render()
    {
        return view('larareviews::components.grid');
    }
}
