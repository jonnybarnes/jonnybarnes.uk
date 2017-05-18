<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndieWebUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('indie_web_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('me')->unique();
            $table->text('token')->nullable();
            $table->string('syntax')->default('json');
            $table->jsonb('syndication')->nullable();
            $table->string('mediaEndpoint')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('indie_web_users');
    }
}
