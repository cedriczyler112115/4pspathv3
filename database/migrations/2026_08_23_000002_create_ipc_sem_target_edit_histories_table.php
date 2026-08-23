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
        Schema::create('ipc_sem_target_edit_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sem_target_id')->index();
            $table->unsignedBigInteger('sem_item_id')->nullable()->index();
            $table->string('field_name')->index();
            $table->text('original_value')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('last_edited_value')->nullable();
            $table->text('justification')->nullable();
            $table->unsignedBigInteger('user_id')->index();
            $table->dateTime('date_created')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipc_sem_target_edit_histories');
    }
};
