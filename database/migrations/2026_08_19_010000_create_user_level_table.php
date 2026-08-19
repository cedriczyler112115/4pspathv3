<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_level')) {
            return;
        }

        Schema::create('user_level', function (Blueprint $table): void {
            $table->increments('level_id');
            $table->string('level_name', 50);
            $table->tinyInteger('is_status')->default(1);
            $table->unique('level_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_level');
    }
};
