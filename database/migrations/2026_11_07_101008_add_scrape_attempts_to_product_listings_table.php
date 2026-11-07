<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->unsignedTinyInteger('scrape_attempts')->default(0)->after('scrape_requested_at');
        });

        DB::table('product_listings')
            ->where('status', 'failed')
            ->update(['scrape_attempts' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_listings', function (Blueprint $table) {
            $table->dropColumn('scrape_attempts');
        });
    }
};
