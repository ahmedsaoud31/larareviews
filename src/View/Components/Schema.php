<?php

namespace LaraReviews\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Model;

class Schema extends Component
{
    public array $schemaData;

    public function __construct(
        public Model $reviewable,
        public string $itemType = 'Product',
        public ?string $name = null,
        public ?string $description = null,
        public ?string $image = null
    ) {
        $this->name = $name ?? ($reviewable->title ?? $reviewable->name ?? 'Tour Experience');
        $this->description = $description ?? ($reviewable->description ?? '');
        $this->image = $image ?? ($reviewable->image_url ?? $reviewable->cover_image ?? null);

        $avg = method_exists($reviewable, 'getAverageRating') ? $reviewable->getAverageRating() : 0.0;
        $total = method_exists($reviewable, 'getTotalReviewsCount') ? $reviewable->getTotalReviewsCount() : 0;
        $reviews = method_exists($reviewable, 'laraReviews') ? $reviewable->laraReviews()->limit(10)->get() : collect();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $itemType,
            'name' => $this->name,
            'description' => $this->description,
        ];

        if ($this->image) {
            $schema['image'] = $this->image;
        }

        if ($total > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $avg,
                'reviewCount' => (string) $total,
                'bestRating' => '5',
                'worstRating' => '1',
            ];

            $schema['review'] = [];
            foreach ($reviews as $rev) {
                $schema['review'][] = [
                    '@type' => 'Review',
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (string) $rev->rating,
                        'bestRating' => '5',
                    ],
                    'author' => [
                        '@type' => 'Person',
                        'name' => $rev->reviewer_name ?? 'Traveler',
                    ],
                    'datePublished' => $rev->review_date?->format('Y-m-d'),
                    'reviewBody' => $rev->content,
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => config("larareviews.ui.platform_names.{$rev->platform}", ucfirst($rev->platform)),
                    ],
                ];
            }
        }

        $this->schemaData = $schema;
    }

    public function render()
    {
        return view('larareviews::components.schema');
    }
}
