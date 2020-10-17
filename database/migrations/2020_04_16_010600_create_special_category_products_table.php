<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpecialCategoryProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_category_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->string('refference');
            $table->enum('featured',[0,1])->default(0);
            $table->enum('new_arrival',[0,1])->default(0);
            $table->enum('sale',[0,1])->default(0);
            $table->decimal('price',20,2);
            $table->decimal('after_discount',20,2)->nullable();
            $table->text('description');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('special_category_products');
    }
}
