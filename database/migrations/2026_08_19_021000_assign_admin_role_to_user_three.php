<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('model_has_roles') || ! Schema::hasColumn('roles', 'guard_name') || ! User::query()->whereKey(3)->exists()) {
            return;
        }

        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        DB::table('model_has_roles')->updateOrInsert([
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => 3,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', 3)->delete();
        }
    }
};
