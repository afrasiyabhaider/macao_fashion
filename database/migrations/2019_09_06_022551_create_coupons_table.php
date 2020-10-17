<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('barcode');
            $table->integer('gift_card_id')->unsigned()->nullable();
            $table->string('coupon_id')->nullable();
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->float('value');
            $table->float('orig_value');
            $table->enum('barcode_type', ['C39', 'C128', 'EAN-13', 'EAN-8', 'UPC-A', 'UPC-E', 'ITF-14']);
            $table->enum('isActive', ['active', 'expired', 'consumed', 'inactive', 'cancell']);
            $table->string('transaction_id')->nullable();
            $table->dateTime('start_date'); 
            $table->text('details')->nullable();  
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->integer('isUsed')->default('0');
            $table->timestamps();

            $table->index('name');
            $table->index('business_id');
            $table->index('barcode');
            $table->index('start_date'); 
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
}
