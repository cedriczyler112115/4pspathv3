<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sidebar_menu_items')) {
            return;
        }

        Schema::create('sidebar_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('sidebar_menu_items')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('key')->nullable()->unique();
            $table->string('href')->nullable();
            $table->string('icon')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('badge_cls')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['parent_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sidebar_menu_items');
    }
};
