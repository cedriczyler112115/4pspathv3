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
        Schema::table('ipc_semester', function (Blueprint $table) {
            if (! Schema::hasColumn('ipc_semester', 'lock')) {
                $table->tinyInteger('lock')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipc_semester', function (Blueprint $table) {
            if (Schema::hasColumn('ipc_semester', 'lock')) {
                $table->dropColumn('lock');
            }
        });
    }
};
