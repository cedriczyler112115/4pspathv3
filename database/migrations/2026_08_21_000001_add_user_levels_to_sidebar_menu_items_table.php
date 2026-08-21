<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sidebar_menu_items') && ! Schema::hasColumn('sidebar_menu_items', 'user_levels')) {
            Schema::table('sidebar_menu_items', function (Blueprint $table): void {
                $table->json('user_levels')->nullable()->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sidebar_menu_items') && Schema::hasColumn('sidebar_menu_items', 'user_levels')) {
            Schema::table('sidebar_menu_items', function (Blueprint $table): void {
                $table->dropColumn('user_levels');
            });
        }
    }
};
