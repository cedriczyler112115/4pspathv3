<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'contact_number')) {
                $table->integer('contact_number')->nullable();
            }
            if (! Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable();
            }
            if (! Schema::hasColumn('users', 'designation')) {
                $table->string('designation')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_status')) {
                $table->tinyInteger('is_status')->default(1);
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
            $table->dropColumn(['contact_number', 'position', 'designation', 'is_status']);
        });
    }
}
