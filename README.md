# LaraReviews - Multi-Platform Review Management for Laravel

**LaraReviews** is a unified Laravel package designed to aggregate, manage, link, and display reviews from **TripAdvisor**, **Viator**, **GetYourGuide**, **Google Reviews**, and custom sources directly on tour, activity, or hotel pages.

---

## Features

- 🔗 **Tour Mapping (`HasReviews` Trait)**: Easily connect any local Laravel model (e.g. `App\Models\Tour`) to external review platform entities (TripAdvisor location ID, Viator product code, etc.).
- 🔌 **Driver-Based Architecture**: Modular drivers for TripAdvisor, Viator, GetYourGuide, Google Places, and Custom/Manual reviews.
- ⚡ **Auto-Sync & CLI Commands**: Sync reviews on-demand, scheduled, or via queued jobs (`php artisan larareviews:sync`).
- 📊 **Aggregate Ratings & Breakdown**: Combined overall score, star distribution percentages, and platform-specific summaries.
- 🎨 **Modern Blade Components**: Ready-to-use `<x-larareviews-summary>`, `<x-larareviews-list>`, `<x-larareviews-grid>`, `<x-larareviews-badge>`, and `<x-larareviews-schema>`.
- 🔍 **Google SEO Rich Snippets**: Built-in JSON-LD `AggregateRating` and `Review` schema generator.

---

## Installation

Add the package to your Laravel application `composer.json` or local packages repository:

```bash
composer require ahmedsaoud31/larareviews:main-dev
```

Publish configuration and run migrations:

```bash
php artisan vendor:publish --tag="larareviews-config"
php artisan vendor:publish --tag="larareviews-migrations"
php artisan migrate
```

---

## Usage Guide

### 1. Attach `HasReviews` Trait to your Tour Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LaraReviews\Traits\HasReviews;

class Tour extends Model
{
    use HasReviews;

    // ...
}
```

### 2. Connect Tour to External Review Platforms

You can connect your local tour to external review platforms programmatically or via CLI:

#### Programmatically:
```php
$tour = Tour::find(1);

// Connect TripAdvisor
$tour->connectReviewPlatform(
    platform: 'tripadvisor',
    externalId: '1234567',
    externalUrl: 'https://www.tripadvisor.com/Attraction_Review-g1234567.html'
);

// Connect Viator
$tour->connectReviewPlatform(
    platform: 'viator',
    externalId: 'P12345',
    externalUrl: 'https://www.viator.com/tours/p-12345'
);

// Connect Google Places
$tour->connectReviewPlatform(
    platform: 'google',
    externalId: 'ChIJN1t_tDeuEmsRUsoyG83frY4'
);
```

#### Via Artisan CLI:
```bash
php artisan larareviews:link "App\Models\Tour" 1 tripadvisor 1234567 --url="https://tripadvisor.com/..."
```

---

### 3. Sync Reviews

To sync reviews manually or in a cron job:

```bash
php artisan larareviews:sync
```

Optionally filter by platform or model:
```bash
php artisan larareviews:sync --platform=tripadvisor --model="App\Models\Tour"
```

Or dispatch via queued background job:
```php
use LaraReviews\Jobs\SyncModelReviewsJob;

SyncModelReviewsJob::dispatch($tour);
```

---

### 4. Display Reviews on Tour Pages (Blade Components)

#### A. Full Review List with Filters & Badges
```html
<x-larareviews-list :reviewable="$tour" :perPage="10" />
```

#### B. Summary & Rating Distribution Box
```html
<x-larareviews-summary :reviewable="$tour" />
```

#### C. Responsive Card Grid
```html
<x-larareviews-grid :reviewable="$tour" :limit="6" :cols="3" />
```

#### D. Compact Rating Badge (For Tour Cards / Lists)
```html
<x-larareviews-badge :reviewable="$tour" />
```

#### E. SEO Rich Snippet Schema (JSON-LD)
Place inside the `<head>` of your tour page:
```html
<x-larareviews-schema :reviewable="$tour" itemType="Product" />
```

---

## Configuration (`config/larareviews.php`)

```php
return [
    'default' => 'tripadvisor',

    'drivers' => [
        'tripadvisor' => [
            'api_key' => env('TRIPADVISOR_API_KEY'),
        ],
        'viator' => [
            'api_key' => env('VIATOR_API_KEY'),
        ],
        'google' => [
            'api_key' => env('GOOGLE_PLACES_API_KEY'),
        ],
    ],
];
```

---

## License

The MIT License (MIT).
