<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTimelineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('timeline', function (Blueprint $table) {
            $platforms = config('platforms');
            $modes = config('modes');
            $table->string('uid');
            $table->string('username');
            $table->string('match');
            $table->timestamps();
            foreach($platforms as $p) {
              foreach($modes as $m => $mode) {
                foreach($mode as $s) {
                  $table->integer("$p:$m:$s")->nullable();
                }
              }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timeline');
    }
}
