<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apprenant extends Model
{

    protected $fillable = [

        'created_by',

        'user_id',

        'matricule',

        'date_naissance',

        'sexe',

        'situation_matrimoniale',

        'adresse',

        'telephone',

        'email',

        'niveau_etude',

        'fonction',

        'niveau_informatique',

        'photo',

        'signature',


    ];



    protected $casts = [

        'date_naissance' => 'date',


    ];





    /**
     * Un apprenant appartient à un utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }





    /**
     * Admin ou gestionnaire qui a créé l'apprenant.
     */
    public function createur()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /**
     * Les diplômes et attestations de l'apprenant.
     */
    public function diplomes()
    {
        return $this->hasMany(
            DiplomeAttestation::class
        );
    }





    /**
     * Les messages envoyés dans la boîte à idées.
     */
    public function boiteIdees()
    {
        return $this->hasMany(
            BoiteIdee::class
        );
    }


    public function inscriptions()
{
    return $this->hasMany(Inscription::class);
}


}