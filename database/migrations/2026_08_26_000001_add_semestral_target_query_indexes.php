<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('ipc_semester', ['user_id', 'year', 'semester'], 'ipc_semester_user_year_semester_index');
        $this->addIndexIfMissing('ipc_sem_targets_indicator', ['semester_id', 'kra_category', 'display_order', 'id'], 'ipc_sem_targets_indicator_filters_index');
        $this->addIndexIfMissing('ipc_sem_targets_indicator', ['semester_id', 'target_status', 'kra_category'], 'ipc_sem_targets_indicator_status_index');
        $this->addIndexIfMissing('ipc_sem_targets_indicator_itemlist', ['sem_target_id', 'display_order', 'id'], 'ipc_sem_targets_indicator_itemlist_order_index');
        $this->addIndexIfMissing('ipc_sem_targets_indicator_itemlist', ['sem_target_id', 'new_semester'], 'ipc_sem_targets_indicator_itemlist_copy_index');
        $this->addIndexIfMissing('ipc_sem_target_edit_histories', ['sem_target_id', 'sem_item_id', 'field_name', 'id'], 'ipc_sem_target_edit_histories_lookup_index');
        $this->addIndexIfMissing('ipc_sem_target_edit_histories', ['sem_target_id', 'date_created'], 'ipc_sem_target_edit_histories_target_date_index');
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('ipc_semester', 'ipc_semester_user_year_semester_index');
        $this->dropIndexIfPresent('ipc_sem_targets_indicator', 'ipc_sem_targets_indicator_filters_index');
        $this->dropIndexIfPresent('ipc_sem_targets_indicator', 'ipc_sem_targets_indicator_status_index');
        $this->dropIndexIfPresent('ipc_sem_targets_indicator_itemlist', 'ipc_sem_targets_indicator_itemlist_order_index');
        $this->dropIndexIfPresent('ipc_sem_targets_indicator_itemlist', 'ipc_sem_targets_indicator_itemlist_copy_index');
        $this->dropIndexIfPresent('ipc_sem_target_edit_histories', 'ipc_sem_target_edit_histories_lookup_index');
        $this->dropIndexIfPresent('ipc_sem_target_edit_histories', 'ipc_sem_target_edit_histories_target_date_index');
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $index) || ! Schema::hasColumns($table, $columns)) {
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
