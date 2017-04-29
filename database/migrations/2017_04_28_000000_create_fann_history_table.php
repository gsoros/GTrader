<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFannHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fann_history', function (Blueprint $table) {
            $table->integer('strategy_id')->unsigned()->index();
            $table->integer('epoch')->unsigned()->index();
            $table->string('name')->index();
            $table->decimal('value', 11, 4)->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fann_history');
    }
}
