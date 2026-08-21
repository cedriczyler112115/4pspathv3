<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('users', ['division_id', 'section_id', 'is_status'], 'users_directory_filters_index');
        $this->addIndexIfMissing('sidebar_menu_items', ['parent_id', 'is_active', 'sort_order'], 'sidebar_menu_tree_index');
        $this->addIndexIfMissing('ipc_targets_indicators', ['user_id', 'target_status', 'target_year', 'kra_category'], 'annual_targets_filters_index');
        $this->addIndexIfMissing('ipc_targets_indicators_itemlist', ['ind_id', 'indi_status', 'display_order'], 'annual_target_items_filters_index');
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('users', 'users_directory_filters_index');
        $this->dropIndexIfPresent('sidebar_menu_items', 'sidebar_menu_tree_index');
        $this->dropIndexIfPresent('ipc_targets_indicators', 'annual_targets_filters_index');
        $this->dropIndexIfPresent('ipc_targets_indicators_itemlist', 'annual_target_items_filters_index');
    }

    /** @param list<string> $columns */
    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns) || Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $index));
    }

    private function dropIndexIfPresent(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
    }
};
