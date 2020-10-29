<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalePrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_priorities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('priority');
            $table->unsignedBigInteger('location_id');
            $table->timestamps();

            $table->unique('location_id')->references('id')->on('business_locations')->onDelete('cascde');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_priorities');
    }
}
