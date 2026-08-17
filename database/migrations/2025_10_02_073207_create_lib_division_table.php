<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLibDivisionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lib_division', function (Blueprint $table) {
            $table->id(); // INT AUTO_INCREMENT PRIMARY KEY
            $table->string('division_name', 150); // VARCHAR(150) NOT NULL
            $table->string('division_head', 150); // VARCHAR(150) NOT NULL
            $table->tinyInteger('div_status')->unsigned(); // TINYINT(3) UNSIGNED NOT NULL
            $table->string('head_pos', 150); // VARCHAR(150) NOT NULL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lib_division');
    }
}
