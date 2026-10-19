<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- 1. Import ini

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable; // <-- 2. Pasang HasRoles di sini

    protected $fillable = [
        'name',
        'email',
        'password',
        'cabang_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
