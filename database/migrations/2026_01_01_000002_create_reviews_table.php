<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // Polymorphic link to local entity (e.g. Tour)
            $table->morphs('reviewable');

            // Foreign key to optional review_mapping entry
            $table->foreignId('review_mapping_id')
                ->nullable()
                ->constrained('review_mappings')
                ->nullOnDelete();

            // Platform name
            $table->string('platform', 50);

            // External unique review ID on platform
            $table->string('external_id');

            // Reviewer information
            $table->string('reviewer_name')->nullable();
            $table->text('reviewer_avatar')->nullable();
            $table->string('reviewer_location')->nullable();

            // Rating score (1.0 to 5.0)
            $table->decimal('rating', 3, 2)->default(5.00);

            // Content
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $table->timestamp('review_date')->nullable();

            // Metadata & Attributes
            $table->string('language', 10)->default('en');
            $table->text('original_url')->nullable();
            $table->boolean('verified')->default(true);
            $table->json('photos')->nullable();
            $table->text('response')->nullable(); // Management response
            $table->json('raw_data')->nullable(); // Original platform JSON response

            $table->timestamps();

            // Indexes
            $table->unique(['reviewable_type', 'reviewable_id', 'platform', 'external_id'], 'review_unique_index');
            $table->index(['reviewable_type', 'reviewable_id', 'rating']);
            $table->index('review_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
