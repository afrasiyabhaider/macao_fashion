<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftCardsTable extends Migration
{
    /**
     * Run the migrations.
     *name,barcode,business_id,type,applicable,product_id,brand_id,value,details,barcode_type,created_by,start_date,expiry_date,isActive,isUsed
     * @return void
     */
    public function up()
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->increments('id'); 
            $table->string('name');
            $table->string('barcode');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->enum('type', ['percentage', 'fixed']);
            $table->enum('applicable', ['any', 'one']);
            $table->integer('product_id')->unsigned()->nullable()->comment = 'If applicable on Specific Product only';
            
            $table->integer('brand_id')->unsigned()->nullable();
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
              
            $table->integer('value');
            $table->text('details')->nullable();
            $table->enum('barcode_type', ['C39', 'C128', 'EAN-13', 'EAN-8', 'UPC-A', 'UPC-E', 'ITF-14']);
            $table->integer('transaction_id')->nullable();
            $table->integer('created_by')->unsigned();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->dateTime('start_date'); 
            $table->dateTime('expiry_date'); 
            $table->dateTime('consume_date')->nullable();
            $table->enum('isActive', ['active', 'expired', 'consumed', 'inactive', 'cancell']);
             $table->integer('isUsed')->default('0');
            $table->timestamps();

            //Indexing
            $table->index('name');
            $table->index('business_id');
            $table->index('barcode');
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
        Schema::dropIfExists('gift_cards');
    }
}
