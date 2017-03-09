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
            $table->uuid('id');
            $table->varchar('client_id')->nullable();
            $table->varchar('filetype');
            $table->unsignedInteger('note_id')->nullable();
            $table->timestamps();

            $table->primary('id');
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
        Scheme::table('media_endpoint', function (Blueprint $table) {
            $table->dropForeign(['note_id']);
        });
        Schema::dropIfExists('media_endpoint');
    }
}
