<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = ['kode_transaksi', 'total', 'idempotency_key', 'request_hash', 'user_id', 'cabang_id'];

    protected $casts = ['total' => 'decimal:2'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function detail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }
}
