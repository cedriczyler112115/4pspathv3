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
        Schema::table('ipc_changes', function (Blueprint $table) {
            // Add a nullable justification text column for change rationale
            if (!Schema::hasColumn('ipc_changes', 'justification')) {
                $table->text('justification')->nullable()->after('action');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipc_changes', function (Blueprint $table) {
            if (Schema::hasColumn('ipc_changes', 'justification')) {
                $table->dropColumn('justification');
            }
        });
    }
};