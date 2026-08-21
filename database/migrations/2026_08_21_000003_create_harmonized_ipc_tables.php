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
        if (! Schema::hasTable('harmonized_ipc_targets_indicators')) {
            Schema::create('harmonized_ipc_targets_indicators', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('target_group_id')->nullable();
                $table->string('target_year', 20)->nullable()->index();
                $table->integer('target_sem')->nullable();
                $table->integer('kra_category')->nullable()->index();
                $table->integer('display_order')->nullable();
                $table->text('activity')->nullable();
                $table->text('remarks')->nullable();
                $table->integer('target_status')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('date_created')->nullable();
                $table->unsignedBigInteger('modified_by')->nullable();
                $table->dateTime('last_date_modified')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'target_status', 'target_year', 'kra_category'], 'harmonized_targets_filters_idx');
            });
        }

        if (! Schema::hasTable('harmonized_ipc_targets_indicators_itemlist')) {
            Schema::create('harmonized_ipc_targets_indicators_itemlist', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('ind_id')->index();
                $table->integer('display_order')->nullable();
                $table->integer('new_semester')->nullable();
                $table->text('description')->nullable();
                $table->string('weight')->nullable();
                $table->text('quantity')->nullable();
                $table->text('quality')->nullable();
                $table->text('timeliness')->nullable();
                $table->text('remarks')->nullable();
                $table->integer('indi_status')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('date_created')->nullable();
                $table->unsignedBigInteger('modified_by')->nullable();
                $table->text('rg_efficiency_')->nullable();
                $table->text('rg_quality_')->nullable();
                $table->text('rg_timeliness_')->nullable();
                $table->text('rg_ratingperiod_')->nullable();
                $table->text('rg_mov_')->nullable();
                $table->text('rg_remarks_')->nullable();
                $table->dateTime('date_modified')->nullable();
                $table->timestamps();

                $table->index(['ind_id', 'indi_status', 'display_order'], 'harmonized_target_items_filters_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harmonized_ipc_targets_indicators_itemlist');
        Schema::dropIfExists('harmonized_ipc_targets_indicators');
    }
};
