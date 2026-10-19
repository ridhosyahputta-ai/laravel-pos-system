<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }

    protected $guarded = [];
}
