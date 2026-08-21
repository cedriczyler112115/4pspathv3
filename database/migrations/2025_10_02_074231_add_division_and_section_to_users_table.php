<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDivisionAndSectionToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'division_id')) {
                $table->foreignId('division_id')
                    ->nullable()
                    ->constrained('lib_division')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'section_id')) {
                $table->foreignId('section_id')
                    ->nullable()
                    ->constrained('lib_section')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraints first, then columns
            $table->dropForeign(['division_id']);
            $table->dropForeign(['section_id']);
            $table->dropColumn(['division_id', 'section_id']);
        });
    }
}
