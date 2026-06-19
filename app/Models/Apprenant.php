<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apprenant extends Model
{
    protected $fillable = [
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
        'module_choisi',
        'horaire_choisi',
        'photo',
        'signature',
        'date_inscription',
        'statut'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function diplomes()
    {
        return $this->hasMany(DiplomeAttestation::class);
    }

    public function boiteIdees()
    {
        return $this->hasMany(BoiteIdee::class);
    }
}