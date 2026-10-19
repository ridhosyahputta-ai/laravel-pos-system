<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->restrictOnDelete();
        });
        Schema::table('transaksis', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('cabang_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cabang_id');
            $table->dropColumn('is_active');
        });
    }
};
