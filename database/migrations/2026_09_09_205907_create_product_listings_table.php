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
        Schema::create('product_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('store');
            $table->string('url');
            $table->decimal('current_price', 10, 2);
            $table->string('currency');
            $table->timestamp('last_scraped_at')->nullable();
            $table->string('last_scrape_status')->nullable();
            $table->string('last_scrape_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        Schema::dropIfExists('product_listings');
    }
};
