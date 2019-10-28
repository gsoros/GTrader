<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTradesSignal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->bigInteger('signal_time')->unsigned()->nullable()->index();
            $comment = 'Spot markets: percentage. Futures and Swap: # of contracts. Signed to indicate direction.';
            $table->bigInteger('signal_position')->nullable()->comment($comment);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn('signal_time');
            $table->dropColumn('signal_position');
        });
    }
}
