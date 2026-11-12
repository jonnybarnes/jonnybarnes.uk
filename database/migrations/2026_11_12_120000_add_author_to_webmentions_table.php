<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Existing rows are backfilled with `webmentions:resolve-authors`, as
     * resolving an author can involve fetching the author’s page.
     */
    public function up(): void
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->json('author')->nullable()->after('mf2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->dropColumn('author');
        });
    }
};
