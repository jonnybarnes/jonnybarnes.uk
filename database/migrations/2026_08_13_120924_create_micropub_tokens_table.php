<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('micropub_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash')->unique();
            $table->string('client_id');
            $table->string('me');
            $table->string('scope');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('micropub_tokens');
    }
};
