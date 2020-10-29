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
            $table->unsignedBigInteger('priority_1');
            $table->unsignedBigInteger('priority_2');
            $table->unsignedBigInteger('priority_3');
            $table->unsignedBigInteger('priority_4');
            $table->timestamps();

            $table->unique('priority_1')->references('id')->on('business_locations')->onDelete('cascde');
            $table->unique('priority_2')->references('id')->on('business_locations')->onDelete('cascde');
            $table->unique('priority_3')->references('id')->on('business_locations')->onDelete('cascde');
            $table->unique('priority_4')->references('id')->on('business_locations')->onDelete('cascde');
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
