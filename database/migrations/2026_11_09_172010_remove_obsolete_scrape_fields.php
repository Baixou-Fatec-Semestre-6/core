<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->dropColumn(['scrape_attempts', 'scrape_requested_at']);
        });

        Schema::table('scrape_requests', function (Blueprint $table) {
            $table->dropColumn('attempt_number');
        });
    }

    public function down(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->timestamp('scrape_requested_at')->nullable();
            $table->unsignedTinyInteger('scrape_attempts')->default(0);
        });

        Schema::table('scrape_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempt_number')->nullable();
        });
    }
};
