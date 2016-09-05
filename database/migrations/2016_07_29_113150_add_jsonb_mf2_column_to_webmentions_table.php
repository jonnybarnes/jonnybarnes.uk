<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJsonbMf2ColumnToWebmentionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->jsonb('mf2')->nullable();
            $table->index(['mf2']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->dropIndex(['mf2']);
            $table->dropColumn('mf2');
        });
    }
}
