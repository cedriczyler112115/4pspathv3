<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLibSectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('lib_section')) {
            return;
        }

        Schema::create('lib_section', function (Blueprint $table) {
            $table->id(); // INT AUTO_INCREMENT PRIMARY KEY
            $table->string('section_name', 150); // VARCHAR(150) NOT NULL
            $table->foreignId('division_id')->constrained('lib_division'); // FK to lib_division(id)
            $table->string('sec_acronym', 50)->nullable(); // VARCHAR(50)
            $table->tinyInteger('sec_status')->unsigned(); // TINYINT
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
        Schema::dropIfExists('lib_section');
    }
}
