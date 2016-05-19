<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('notes')) {
            Schema::create('notes', function (Blueprint $table) {
                $table->increments('id');
                $table->text('note');
                $table->string('in_reply_to')->nullable();
                $table->string('shorturl', 20)->nullable();
                $table->string('location')->nullable();
                $table->tinyInteger('photo')->nullable();
                $table->string('tweet_id')->nullable();
                $table->string('client_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
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
        Schema::drop('notes');
    }
}
