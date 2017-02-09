<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCandlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('candles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('time')->unsigned()->index();
            $table->enum('exchange', ['OKCoin_Futures'])->index();
            $table->string('symbol');
            $table->enum('resolution', ['60','180','300','900','1800','3600','7200','14400','21600','43200','86400','259200','604800']);
            $table->float('open', 11, 4)->unsigned();
            $table->float('high', 11, 4)->unsigned();
            $table->float('low', 11, 4)->unsigned();
            $table->float('close', 11, 4)->unsigned();
            $table->bigInteger('volume')->unsigned()->nullable()->default(NULL);
            $table->index(['symbol', 'resolution']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('candle');
    }
}
