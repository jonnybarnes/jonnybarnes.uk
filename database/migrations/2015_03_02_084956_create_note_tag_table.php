<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoteTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('note_tag', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('note_id')->unsigned();
            $table->integer('tag_id')->unsigned();
            $table->foreign('note_id')->references('id')->on('notes');
            $table->foreign('tag_id')->references('id')->on('tags');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('note_tag');
    }
}
