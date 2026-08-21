<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIpcRequestChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('ipc_request_changes')) {
            return;
        }

        Schema::create('ipc_request_changes', function (Blueprint $table) {
            $table->id();
            $table->integer('tarid');
            $table->integer('item_id');
            $table->integer('semid')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->string('changes_category', 50);
            $table->text('old_target')->nullable();
            $table->text('new_target')->nullable();
            $table->text('justification')->nullable();
            $table->integer('changes_status');
            $table->integer('created_by');
            $table->dateTime('date_created');
            $table->integer('approved_by')->nullable();
            $table->dateTime('date_approved')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ipc_request_changes');
    }
}
