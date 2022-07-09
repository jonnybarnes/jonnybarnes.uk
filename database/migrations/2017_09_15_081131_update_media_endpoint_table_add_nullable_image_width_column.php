<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateMediaEndpointTableAddNullableImageWidthColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media_endpoint', function (Blueprint $table) {
            $table->text('image_widths')->nullable();
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
            $table->dropColumn('image_widths');
        });
    }
}
