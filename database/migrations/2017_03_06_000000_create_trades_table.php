<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('time')->unsigned()->index();
            $table->bigInteger('remote_id')->unsigned()->index();
            $table->integer('exchange_id')->unsigned()->index();
            $table->integer('symbol_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->integer('bot_id')->unsigned()->nullable()->index();
            $table->decimal('amount_ordered', 11, 4)->unsigned();
            $table->decimal('amount_filled', 11, 4)->nullable()->unsigned();
            $table->decimal('price', 11, 4)->unsigned();
            $table->decimal('avg_price', 11, 4)->nullable()->unsigned();
            $table->string('action')->nullable();
            $table->string('type')->nullable();
            $table->decimal('fee', 11, 6)->nullable();
            $table->string('fee_currency')->nullable();
            $table->string('status')->nullable();
            $table->integer('leverage')->unsigned()->nullable()->index();
            $table->string('contract')->nullable()->index();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trades');
    }
}
