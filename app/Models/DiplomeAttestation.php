<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Apprenant;

class DiplomeAttestation extends Model
{
    /**
     * Les champs autorisés lors de la création et modification.
     */
    protected $fillable = [

        // Relation avec l'apprenant
        'apprenant_id',

        // Type de document
        'type_document',

        // Informations personnelles
        'prenom',
        'nom',
        'date_naissance',
        'lieu_naissance',
        'telephone',
        'adresse',
        'fonction',

        // Informations sur la formation
        'formation',
        'duree_formation',
        'periode_formation',
        'module_suivi',
        'annee_academique',

        // Informations de la demande
        'lieu_demande',
        'date_demande',
        'fichier_demande',

        // Informations du document officiel
        'numero_document',
        'date_validation',
        'date_delivrance',
        'fichier_pdf',

        // Suivi administratif
        'statut',
        'motif_refus',
        'date_retrait'
    ];

    /**
     * Conversion automatique des dates.
     */
    protected $casts = [
        'date_naissance' => 'date',
        'date_demande' => 'date',
        'date_validation' => 'date',
        'date_delivrance' => 'date',
        'date_retrait' => 'date',
    ];

    /**
     * Relation : un document appartient à un apprenant.
     */
    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }
}
