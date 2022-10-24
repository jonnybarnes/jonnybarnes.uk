<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('syndication_targets', function (Blueprint $table) {
            $table->string('service_name')->nullable()->change();
            $table->string('service_url')->nullable()->change();
            $table->string('service_photo')->nullable()->change();
            $table->string('user_name')->nullable()->change();
            $table->string('user_url')->nullable()->change();
            $table->string('user_photo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('syndication_targets', function (Blueprint $table) {
            //
        });
    }
};
