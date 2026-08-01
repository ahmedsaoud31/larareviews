<?php

namespace LaraReviews\Console\Commands;

use Illuminate\Console\Command;

class LinkPlatformCommand extends Command
{
    protected $signature = 'larareviews:link 
                            {model : Class name of reviewable entity, e.g. "App\\Models\\Tour"}
                            {id : Entity primary key ID}
                            {platform : Platform name (tripadvisor, viator, getyourguide, google)}
                            {external_id : External location or product ID}
                            {--url= : Optional platform web URL}';

    protected $description = 'Link a local model instance (e.g., Tour) with an external review platform entity';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $id = $this->argument('id');
        $platform = strtolower(trim($this->argument('platform')));
        $externalId = $this->argument('external_id');
        $url = $this->option('url');

        if (!class_exists($modelClass)) {
            $this->error("Class {$modelClass} does not exist.");
            return self::FAILURE;
        }

        $model = $modelClass::find($id);

        if (!$model) {
            $this->error("Record {$modelClass} with ID {$id} not found.");
            return self::FAILURE;
        }

        if (!method_exists($model, 'connectReviewPlatform')) {
            $this->error("Model {$modelClass} must use LaraReviews\\Traits\\HasReviews trait.");
            return self::FAILURE;
        }

        $this->info("Linking {$modelClass} #{$id} with {$platform} ID: {$externalId}...");

        $mapping = $model->connectReviewPlatform($platform, $externalId, $url);

        $this->info("✓ Successfully connected {$platform} to {$modelClass} #{$id}!");
        $this->comment("Initial review sync triggered. Last sync status: {$mapping->sync_status}");

        return self::SUCCESS;
    }
}
