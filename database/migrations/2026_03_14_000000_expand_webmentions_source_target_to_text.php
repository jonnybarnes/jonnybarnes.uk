<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->text('source')->change();
            $table->text('target')->change();
        });
    }

    public function down(): void
    {
        Schema::table('webmentions', function (Blueprint $table) {
            $table->string('source')->change();
            $table->string('target')->change();
        });
    }
};
