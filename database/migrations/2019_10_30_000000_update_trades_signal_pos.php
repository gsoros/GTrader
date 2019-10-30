<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTradesSignalPos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trades', function (Blueprint $table) {
            $comment = 'Spot markets: base balance. Futures and Swap: # of contracts, sign indicates direction.';
            $table->decimal('signal_position', 20, 8)->nullable()->comment($comment)->change();
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
            $comment = 'Spot markets: percentage. Futures and Swap: # of contracts. Signed to indicate direction.';
            $table->bigInteger('signal_position')->nullable()->comment($comment)->change();
        });
    }
}
