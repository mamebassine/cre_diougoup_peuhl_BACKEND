<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'password',
        'role',
        'photo',
        'is_active'
    ];

    protected $casts = [
    'password' => 'hashed',
    'is_active' => 'boolean',
];

    protected $hidden = [
        'password',
        'remember_token',
    ];

       public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function apprenant()
    {
        return $this->hasOne(Apprenant::class);
    }
}