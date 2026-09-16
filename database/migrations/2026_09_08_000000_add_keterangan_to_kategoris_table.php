<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('kategoris', 'keterangan')) {
            Schema::table('kategoris', function (Blueprint $table) {
                $table->text('keterangan')->nullable()->after('nama_kategori');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('kategoris', 'keterangan')) {
            Schema::table('kategoris', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
