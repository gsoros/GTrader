<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropLongNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('exchanges', 'long_name')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->dropColumn('long_name');
            });
        }
        if (Schema::hasColumn('symbols', 'long_name')) {
            Schema::table('symbols', function (Blueprint $table) {
                $table->dropColumn('long_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('symbols', 'long_name')) {
            Schema::table('symbols', function (Blueprint $table) {
                $table->string('long_name');
            });
        }
        if (!Schema::hasColumn('exchanges', 'long_name')) {
            Schema::table('exchanges', function (Blueprint $table) {
                $table->string('long_name');
            });
        }
    }
}
