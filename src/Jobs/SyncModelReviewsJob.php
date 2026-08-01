<?php

namespace LaraReviews\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class SyncModelReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Model $model,
        public ?string $platform = null
    ) {}

    public function handle(): void
    {
        if (method_exists($this->model, 'syncReviews')) {
            $this->model->syncReviews($this->platform);
        }
    }
}
