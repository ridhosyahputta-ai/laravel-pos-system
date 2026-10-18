<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = ['kode_transaksi', 'total', 'idempotency_key', 'request_hash'];

    protected $casts = ['total' => 'decimal:2'];

    public function detail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }
}
