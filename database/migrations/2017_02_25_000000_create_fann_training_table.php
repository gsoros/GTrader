<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFannTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fann_training', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('strategy_id')->unsigned()->index();
            $table->string('status')->index();
            $table->integer('exchange_id')->unsigned();
            $table->integer('symbol_id')->unsigned();
            $table->integer('resolution')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fann_training');
    }
}
