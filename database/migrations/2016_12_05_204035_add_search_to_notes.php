<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSearchToNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            DB::statement('ALTER TABLE notes ADD searchable tsvector NULL');
            DB::statement('CREATE INDEX notes_searchable_index ON notes USING GIN (searchable)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP INDEX IF EXISTS notes_searchable_index');
        DB::statement('ALTER TABLE notes DROP searchable');
    }
}
