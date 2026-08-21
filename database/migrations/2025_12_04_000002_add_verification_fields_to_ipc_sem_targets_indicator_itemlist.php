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
        if (! Schema::hasTable('ipc_sem_targets_indicator_itemlist')) {
            return;
        }

        Schema::table('ipc_sem_targets_indicator_itemlist', function (Blueprint $table) {
            if (! Schema::hasColumn('ipc_sem_targets_indicator_itemlist', 'verified')) {
                $table->boolean('verified')->nullable();
            }
            if (! Schema::hasColumn('ipc_sem_targets_indicator_itemlist', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable();
            }
            if (! Schema::hasColumn('ipc_sem_targets_indicator_itemlist', 'date_verified')) {
                $table->timestamp('date_verified')->nullable();
            }
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

