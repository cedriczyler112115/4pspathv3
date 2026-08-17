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
        Schema::create('ipc_changes', function (Blueprint $table) {
            // Table options to mirror MySQL definition
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            // Columns based on the provided MySQL schema
            $table->integer('id')->primary();
            $table->integer('sem_id');
            $table->integer('tarid');
            $table->integer('item_id')->nullable();
            $table->string('old_kra', 25);
            $table->string('new_kra', 25)->nullable();
            $table->text('old_activity');
            $table->text('new_activity')->nullable();
            $table->text('old_success_indicator');
            $table->text('new_success_indicator')->nullable();
            $table->string('action', 20)->nullable()->comment('Add, Edit, Delete');
            $table->integer('created_by');
            $table->dateTime('date_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipc_changes');
    }
};