<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\NotificationChannel;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_monitors', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('listing_id')->constrained('product_listings');
            $table->decimal('target_price', 10, 2);
            $table->string('currency');
            $table->string('notification_channel')->default(NotificationChannel::ALL->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_monitors', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['listing_id']);
        });
        Schema::dropIfExists('product_monitors');
    }
};
