<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrainingHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('training_history', function (Blueprint $table) {
            $table->integer('strategy_id')->unsigned()->index();
            $table->integer('epoch')->unsigned()->index();
            $table->string('name')->index();
            $table->decimal('value', 19, 4);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('training_history');
    }
}
