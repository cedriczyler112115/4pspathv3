<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipc_rg_update', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_sem');
            $table->string('rg_fields', 50);
            $table->text('new_value')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_sem');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipc_rg_update');
    }
};
