<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookmarkTagPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookmark_tag', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bookmark_id');
            $table->unsignedInteger('tag_id');
            $table->timestamps();

            $table->foreign('bookmark_id')->references('id')->on('bookmarks');
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
        Schema::dropIfExists('bookmark_tag');
    }
}
