<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return;
        }

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'sidebar_menu_items')
            ->where('COLUMN_NAME', 'parent_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->first(['CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME']);

        if ($constraint?->REFERENCED_TABLE_NAME === 'sidebar_menu_items') {
            return;
        }

        if ($constraint) {
            DB::statement('ALTER TABLE `sidebar_menu_items` DROP FOREIGN KEY `'.$constraint->CONSTRAINT_NAME.'`');
        }

        // Use a distinct name because the legacy table may still own Laravel's default FK name.
        DB::statement('ALTER TABLE `sidebar_menu_items` ADD CONSTRAINT `sidebar_menu_items_parent_self_foreign` FOREIGN KEY (`parent_id`) REFERENCES `sidebar_menu_items` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT');
    }

    public function down(): void
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return;
        }

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'sidebar_menu_items')
            ->where('COLUMN_NAME', 'parent_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->first(['CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME']);

        if ($constraint?->REFERENCED_TABLE_NAME === 'sidebar_menu_items') {
            DB::statement('ALTER TABLE `sidebar_menu_items` DROP FOREIGN KEY `'.$constraint->CONSTRAINT_NAME.'`');
        }

        if (Schema::hasTable('sidebar_menu_items_old')) {
            DB::statement('ALTER TABLE `sidebar_menu_items` ADD CONSTRAINT `sidebar_menu_items_parent_old_foreign` FOREIGN KEY (`parent_id`) REFERENCES `sidebar_menu_items_old` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT');
        }
    }
};
