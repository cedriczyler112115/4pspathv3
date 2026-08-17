<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lib_division', function (Blueprint $table) {
            $table->string('division_signatory', 150)->nullable()->after('division_head');
            // Add an index to optimize lookups/searches on division_signatory if used frequently
            $table->index('division_signatory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lib_division', function (Blueprint $table) {
            $table->dropIndex(['division_signatory']);
            $table->dropColumn('division_signatory');
        });
    }
};