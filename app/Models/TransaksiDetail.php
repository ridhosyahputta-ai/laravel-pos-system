<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiDetail extends Model
{
    protected $fillable = ['transaksi_id', 'produk_id', 'jumlah', 'harga_satuan', 'subtotal'];

    protected $casts = ['jumlah' => 'integer', 'harga_satuan' => 'decimal:2', 'subtotal' => 'decimal:2'];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
