<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ipc_targets_indicators_itemlist', 'new_semester')) {
            Schema::table('ipc_targets_indicators_itemlist', function (Blueprint $table) {
                $table->integer('new_semester')->nullable()->after('ind_id');
            });

            DB::statement(
                'UPDATE ipc_targets_indicators_itemlist AS itl
                 INNER JOIN ipc_targets_indicators AS iti ON iti.id = itl.ind_id
                 SET itl.new_semester = iti.target_sem'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ipc_targets_indicators_itemlist', function (Blueprint $table) {
            $table->dropColumn('new_semester');
        });
    }
};
