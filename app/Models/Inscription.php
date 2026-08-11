<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{

    protected $fillable = [

        'apprenant_id',

        'formation_id',

        'horaire',

        'date_inscription',

        'statut',

        'etat_formation',

        'created_by'

    ];



    protected $casts = [

        'date_inscription' => 'date',

    ];





    /**
     * Une inscription appartient à un apprenant.
     */
    
public function apprenant()
{
    return $this->belongsTo(Apprenant::class);
}


    /**
     * Une inscription appartient à une formation.
     */
    public function formation()
    {
        return $this->belongsTo(
            Formation::class
        );
    }







    /**
     * L'utilisateur qui a créé l'inscription
     * (Admin ou Gestionnaire)
     */
    public function createur()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


}