<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable for existing accounts; application logic should require these after Google login succeeds.
            $table->string('google_id', 255)->nullable()->after('email');
            $table->string('google_email', 255)->nullable()->after('google_id');
            $table->text('google_access_token')->nullable()->after('google_email');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('google_avatar_url', 500)->nullable()->after('google_token_expires_at');

            $table->index('google_id', 'users_google_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_google_id_index');
            $table->dropColumn([
                'google_id',
                'google_email',
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_avatar_url',
            ]);
        });
    }
};
