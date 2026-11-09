<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->table('audit_logs', function ($collection) {
            $collection->index('created_at');
            $collection->index(['subject_type' => 1, 'subject_id' => 1]);
            $collection->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->table('audit_logs', function ($collection) {
            $collection->dropIndex('created_at_1');
            $collection->dropIndex('subject_type_1_subject_id_1');
            $collection->dropIndex('admin_id_1');
        });
    }
};
