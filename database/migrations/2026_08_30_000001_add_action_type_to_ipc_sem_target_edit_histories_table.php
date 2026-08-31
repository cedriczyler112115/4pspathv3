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
        Schema::table('ipc_sem_target_edit_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('ipc_sem_target_edit_histories', 'action_type')) {
                $table->string('action_type', 50)->nullable()->default('updated')->after('field_name')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipc_sem_target_edit_histories', function (Blueprint $table) {
            if (Schema::hasColumn('ipc_sem_target_edit_histories', 'action_type')) {
                $table->dropColumn('action_type');
            }
        });
    }
};
