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
        if (! Schema::hasTable('lib_harmonized_positions')) {
            Schema::create('lib_harmonized_positions', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('lib_harmonized_positions')->insert([
                ['name' => 'Provincial Link', 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'SWO III', 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Municipal Links', 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'SWA', 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lib_harmonized_positions');
    }
};
