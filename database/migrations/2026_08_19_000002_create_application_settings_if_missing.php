<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        DB::table('settings')->insertOrIgnore([
            [
                'key' => 'app_name',
                'value' => config('app.name', '4Ps PATH v3'),
                'type' => 'string',
                'description' => 'Application display name',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'include_strategic_function',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Show Strategic Function in Annual Target',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['app_name', 'include_strategic_function'])->delete();
    }
};
