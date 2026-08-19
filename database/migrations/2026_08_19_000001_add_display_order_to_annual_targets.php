<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ipc_targets_indicators', 'display_order')) {
            Schema::table('ipc_targets_indicators', function (Blueprint $table): void {
                $table->unsignedInteger('display_order')->nullable()->after('kra_category');
            });
        }

        if (! Schema::hasColumn('ipc_targets_indicators_itemlist', 'display_order')) {
            Schema::table('ipc_targets_indicators_itemlist', function (Blueprint $table): void {
                $table->unsignedInteger('display_order')->nullable()->after('ind_id');
            });
        }

        DB::statement(<<<'SQL'
            UPDATE ipc_targets_indicators AS target
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY user_id, target_year, kra_category
                    ORDER BY date_created ASC, id ASC
                ) AS row_position
                FROM ipc_targets_indicators
            ) AS ranked ON ranked.id = target.id
            SET target.display_order = ranked.row_position
            WHERE target.display_order IS NULL
        SQL);

        DB::statement(<<<'SQL'
            UPDATE ipc_targets_indicators_itemlist AS target
            INNER JOIN (
                SELECT id, ROW_NUMBER() OVER (
                    PARTITION BY ind_id
                    ORDER BY date_created ASC, id ASC
                ) AS row_position
                FROM ipc_targets_indicators_itemlist
            ) AS ranked ON ranked.id = target.id
            SET target.display_order = ranked.row_position
            WHERE target.display_order IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('ipc_targets_indicators_itemlist', function (Blueprint $table): void {
            $table->dropColumn('display_order');
        });

        Schema::table('ipc_targets_indicators', function (Blueprint $table): void {
            $table->dropColumn('display_order');
        });
    }
};
