<?php

namespace LaraReviews\Console\Commands;

use Illuminate\Console\Command;
use LaraReviews\Models\ReviewMapping;

class SyncReviewsCommand extends Command
{
    protected $signature = 'larareviews:sync 
                            {--platform= : Filter by platform (tripadvisor, viator, etc.)}
                            {--mapping= : Sync specific ReviewMapping ID}
                            {--model= : Morphable model class (e.g. App\\Models\\Tour)}';

    protected $description = 'Sync reviews from external review platforms (TripAdvisor, Viator, etc.)';

    public function handle(): int
    {
        $platform = $this->option('platform');
        $mappingId = $this->option('mapping');
        $modelClass = $this->option('model');

        $query = ReviewMapping::query();

        if ($mappingId) {
            $query->where('id', $mappingId);
        }

        if ($platform) {
            $query->where('platform', strtolower(trim($platform)));
        }

        if ($modelClass) {
            $query->where('reviewable_type', $modelClass);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No review mappings found matching criteria.');
            return self::SUCCESS;
        }

        $this->info("Starting review sync for {$count} entity mapping(s)...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $totalSynced = 0;
        $failedCount = 0;

        $query->with('reviewable')->chunk(100, function ($mappings) use ($bar, &$totalSynced, &$failedCount) {
            foreach ($mappings as $mapping) {
                $model = $mapping->reviewable;

                if (!$model || !method_exists($model, 'syncReviews')) {
                    $mapping->markAsFailed("Target model missing or does not use HasReviews trait.");
                    $failedCount++;
                    $bar->advance();
                    continue;
                }

                $result = $model->syncReviews($mapping->platform);
                $totalSynced += $result['synced_reviews'] ?? 0;

                if (!empty($result['errors'])) {
                    $failedCount++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ Review sync completed! {$totalSynced} review(s) updated/created.");

        if ($failedCount > 0) {
            $this->error("⚠️ {$failedCount} mapping(s) encountered errors during sync.");
        }

        return self::SUCCESS;
    }
}
