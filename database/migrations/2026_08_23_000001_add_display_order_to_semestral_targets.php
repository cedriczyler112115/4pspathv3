<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ipc_sem_targets_indicator', 'display_order')) {
            Schema::table('ipc_sem_targets_indicator', function (Blueprint $table): void {
                $table->unsignedInteger('display_order')->nullable()->after('kra_category');
            });
        }

        if (! Schema::hasColumn('ipc_sem_targets_indicator_itemlist', 'display_order')) {
            Schema::table('ipc_sem_targets_indicator_itemlist', function (Blueprint $table): void {
                $table->unsignedInteger('display_order')->nullable()->after('sem_target_id');
            });
        }

        DB::statement(<<<'SQL'
            UPDATE ipc_sem_targets_indicator AS target
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY semester_id, kra_category
                    ORDER BY id ASC
                ) AS row_position
                FROM ipc_sem_targets_indicator
            ) AS ranked ON ranked.id = target.id
            SET target.display_order = ranked.row_position
            WHERE target.display_order IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE ipc_sem_targets_indicator_itemlist AS target
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY sem_target_id
                    ORDER BY id ASC
                ) AS row_position
                FROM ipc_sem_targets_indicator_itemlist
            ) AS ranked ON ranked.id = target.id
            SET target.display_order = ranked.row_position
            WHERE target.display_order IS NULL
        SQL);
    }

    public function down(): void
    {
        if (Schema::hasColumn('ipc_sem_targets_indicator_itemlist', 'display_order')) {
            Schema::table('ipc_sem_targets_indicator_itemlist', function (Blueprint $table): void {
                $table->dropColumn('display_order');
            });
        }

        if (Schema::hasColumn('ipc_sem_targets_indicator', 'display_order')) {
            Schema::table('ipc_sem_targets_indicator', function (Blueprint $table): void {
                $table->dropColumn('display_order');
            });
        }
    }
};
