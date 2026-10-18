<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('category correction preserves legacy descriptions and allows null and longer text', function () {
    Schema::table('kategoris', function (Blueprint $table) {
        $table->string('keterangan', 50)->nullable(false)->change();
    });
    $id = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Legacy', 'keterangan' => 'Original description']);
    $migration = require database_path('migrations/2026_10_18_000001_normalize_keterangan_on_kategoris_table.php');
    $migration->up();

    $this->assertDatabaseHas('kategoris', ['id' => $id, 'nama_kategori' => 'Legacy', 'keterangan' => 'Original description']);
    $column = collect(Schema::getColumns('kategoris'))->firstWhere('name', 'keterangan');
    expect($column['nullable'])->toBeTrue()->and($column['type_name'])->toBe('text');
    DB::table('kategoris')->insert(['nama_kategori' => 'Optional', 'keterangan' => null]);
    DB::table('kategoris')->insert(['nama_kategori' => 'Long', 'keterangan' => str_repeat('x', 2000)]);

    $migration->up();
    expect(DB::table('kategoris')->count())->toBe(3);
    expect(fn () => $migration->down())->toThrow(RuntimeException::class, 'tidak dapat dibatalkan otomatis');
    expect(DB::table('kategoris')->where('nama_kategori', 'Long')->value('keterangan'))->toBe(str_repeat('x', 2000));
});
