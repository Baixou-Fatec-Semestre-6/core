<?php

use App\Enums\NotificationChannel;
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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_monitor_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('target_price', 10, 2);
            $table->string('notification_channel')
                ->default(NotificationChannel::ALL->value);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
