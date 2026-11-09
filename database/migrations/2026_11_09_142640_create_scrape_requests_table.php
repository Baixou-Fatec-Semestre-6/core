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
        Schema::create('scrape_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->foreignId('product_listing_id')
                ->nullable()
                ->constrained('product_listings')
                ->nullOnDelete();
            $table->string('product_name');
            $table->string('store');
            $table->text('listing_url');
            $table->unsignedTinyInteger('attempt_number');
            $table->string('status')->default('dispatching');
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->text('error_reason')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scrape_requests');
    }
};
