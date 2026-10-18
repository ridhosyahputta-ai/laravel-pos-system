<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $fillable = ['nama','kode','harga','stok','kategori_id','supplier_id','keterangan'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transaksiDetails()
    {
        return $this->hasMany(TransaksiDetail::class);
    }
}
