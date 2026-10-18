<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategoris', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Narrowing this column could truncate descriptions or reject existing nulls.
        throw new RuntimeException('Koreksi keterangan kategori tidak dapat dibatalkan otomatis karena berisiko kehilangan data.');
    }
};
