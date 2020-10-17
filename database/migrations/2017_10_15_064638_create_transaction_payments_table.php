<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unsigned();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->enum('is_convert', ['no','gift_card','coupon','bonus_points','unknown'])->nullable();
            $table->decimal('amount', 8, 2)->default(0);
            $table->enum('method', ['cash','card','cheque','bank_transfer','other','gift_card','coupon','unknown','force_price','bonus_points']);

            $table->string('gift_card')->nullable();
            $table->string('coupon')->nullable();
            $table->string('bonus_points')->nullable();
            $table->string('force_price')->nullable();
            $table->string('unknown_price')->nullable();
            $table->string('card_transaction_number')->nullable();
            $table->string('card_number')->nullable();
            $table->enum('card_type', ['visa', 'master'])->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_month')->nullable();
            $table->string('card_year')->nullable();
            $table->string('card_security', 5)->nullable();

            $table->string('cheque_number')->nullable();

            $table->string('bank_account_number')->nullable();

            $table->string('note')->nullable();
            
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
        Schema::dropIfExists('transaction_payments');
    }
}
