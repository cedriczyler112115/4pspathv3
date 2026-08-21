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
        if (Schema::hasTable('harmonized_ipc_targets_indicators')) {
            Schema::table('harmonized_ipc_targets_indicators', function (Blueprint $table): void {
                if (Schema::hasColumn('harmonized_ipc_targets_indicators', 'user_id')) {
                    // Drop old index if present
                    try {
                        $table->dropIndex('harmonized_targets_filters_idx');
                    } catch (\Throwable $e) {
                        // ignore if index does not exist
                    }

                    $table->renameColumn('user_id', 'harmonized_position_id');
                }
            });

            Schema::table('harmonized_ipc_targets_indicators', function (Blueprint $table): void {
                if (! Schema::hasColumn('harmonized_ipc_targets_indicators', 'harmonized_position_id')) {
                    $table->unsignedBigInteger('harmonized_position_id')->nullable()->after('id');
                }

                $table->foreign('harmonized_position_id', 'fk_harmonized_position_id')
                    ->references('id')
                    ->on('lib_harmonized_positions')
                    ->nullOnDelete();

                $table->index(['harmonized_position_id', 'target_status', 'target_year', 'kra_category'], 'harmonized_targets_filters_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('harmonized_ipc_targets_indicators')) {
            Schema::table('harmonized_ipc_targets_indicators', function (Blueprint $table): void {
                try {
                    $table->dropForeign('fk_harmonized_position_id');
                } catch (\Throwable $e) {
                    // ignore
                }

                try {
                    $table->dropIndex('harmonized_targets_filters_idx');
                } catch (\Throwable $e) {
                    // ignore
                }

                if (Schema::hasColumn('harmonized_ipc_targets_indicators', 'harmonized_position_id')) {
                    $table->renameColumn('harmonized_position_id', 'user_id');
                    $table->index(['user_id', 'target_status', 'target_year', 'kra_category'], 'harmonized_targets_filters_idx');
                }
            });
        }
    }
};
