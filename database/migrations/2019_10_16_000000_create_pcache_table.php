<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pcache', function (Blueprint $table) {
            $table->string('cache_key')->primary();
            $table->integer('cache_time')->unsigned();
            $table->index(['cache_key', 'cache_time']);
        });
        DB::statement('ALTER TABLE pcache ADD cache_value LONGBLOB NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pcache');
    }
}
