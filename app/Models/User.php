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



    protected $hidden = [

        'password',

        'remember_token',

    ];



    protected $casts = [

        'password' => 'hashed',

        'is_active' => 'boolean',

    ];




    /**
     * JWT
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }



    public function getJWTCustomClaims()
    {
        return [];
    }




    /**
     * Un utilisateur possède un seul dossier apprenant.
     */
    public function apprenant()
    {
        return $this->hasOne(Apprenant::class);
    }





    /**
     * Les apprenants créés par un admin ou gestionnaire.
     */
    public function apprenantsCrees()
    {
        return $this->hasMany(
            Apprenant::class,
            'created_by'
        );
    }





    /**
     * Les inscriptions créées par un admin ou gestionnaire.
     */
    public function inscriptionsCreees()
    {
        return $this->hasMany(
            Inscription::class,
            'created_by'
        );
    }


}