<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Nullable for historical sales; every new sale requires a UUID token.
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->char('request_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('transaksis')->whereNotNull('idempotency_key')->exists()) {
            throw new RuntimeException('Cannot remove idempotency history from completed sales.');
        }

        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'request_hash']);
        });
    }
};
