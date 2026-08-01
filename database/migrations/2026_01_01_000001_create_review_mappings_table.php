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
        Schema::create('review_mappings', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relation to local model (e.g. App\Models\Tour, Hotel, etc.)
            $table->morphs('reviewable');

            // Platform identifier (tripadvisor, viator, getyourguide, google, custom)
            $table->string('platform', 50);

            // External identifier on target platform (e.g., location ID, product code, place ID)
            $table->string('external_id');

            // External product URL on platform
            $table->text('external_url')->nullable();

            // Custom configuration or extra meta per mapping
            $table->json('settings')->nullable();

            // Sync metadata
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status', 30)->default('pending'); // pending, success, failed
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['reviewable_type', 'reviewable_id', 'platform'], 'reviewable_platform_unique');
            $table->index(['platform', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_mappings');
    }
};
