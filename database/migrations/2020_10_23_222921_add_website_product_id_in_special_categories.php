<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWebsiteProductIdInSpecialCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('special_category_products', function (Blueprint $table) {
            $table->unsignedBigInteger('website_product_id')->after('id');
            $table->foreign('website_product_id')->references('id')->on('website_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_categories', function (Blueprint $table) {
            //
        });
    }
}
