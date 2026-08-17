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
        Schema::table('ipc_sem_targets_indicator_itemlist', function (Blueprint $table) {
            // Add verification fields after has_attachments
            $table->boolean('verified')->nullable()->after('has_attachments');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified');
            $table->timestamp('date_verified')->nullable()->after('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipc_sem_targets_indicator_itemlist', function (Blueprint $table) {
            // Drop the added columns
            $table->dropColumn('date_verified');
            $table->dropColumn('verified_by');
            $table->dropColumn('verified');
        });
    }
};

