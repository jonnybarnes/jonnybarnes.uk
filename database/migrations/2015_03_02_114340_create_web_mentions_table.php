<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWebMentionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webmentions', function (Blueprint $table)
        {
            $table->increments('id');
            $table->string('source');
            $table->string('target');
            $table->integer('commentable_id')->nullable();
            $table->string('commentable_type')->nullable();
            $table->string('type')->nullable();
            $table->text('content');
            $table->tinyInteger('verified')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('webmentions');
    }
}
