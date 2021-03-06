<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateModelsReferenceInWebmentionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webmentions', function (Blueprint $table) {
            DB::statement("UPDATE webmentions SET commentable_type = 'App\\Models\\Note' WHERE commentable_type = 'App\\Note'");
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
            DB::statement("UPDATE webmentions SET commentable_type = 'App\\Note' WHERE commentable_type = 'App\\Models\\Note'");
        });
    }
}
