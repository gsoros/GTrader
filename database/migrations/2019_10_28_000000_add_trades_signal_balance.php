<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTradesSignalBalance extends Migration
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
            $table->decimal('open_balance', 16, 8)->nullable();
            $table->decimal('close_balance', 16, 8)->nullable();
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
            $table->dropColumn('open_balance');
            $table->dropColumn('close_balance');
        });
    }
}
