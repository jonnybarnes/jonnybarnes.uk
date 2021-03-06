<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaEndpointTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_endpoint', function (Blueprint $table) {
            $table->increments('id');
            $table->text('token')->nullable();
            $table->string('path');
            $table->string('type');
            $table->unsignedInteger('note_id')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->foreign('note_id')->references('id')->on('notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media_endpoint', function (Blueprint $table) {
            $table->dropForeign(['note_id']);
        });
        Schema::dropIfExists('media_endpoint');
    }
}
