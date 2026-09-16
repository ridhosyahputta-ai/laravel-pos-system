<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = ['kode_transaksi', 'total'];

    public function detail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }
}
