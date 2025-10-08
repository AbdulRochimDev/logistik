<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('context', 120);
            $table->string('key', 200);
            $table->string('request_hash', 64);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['context', 'key'], 'ux_idempotency_context_key');
            $table->index('last_used_at', 'ix_idempotency_last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
