<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiplomeAttestation extends Model
{
    protected $fillable = [
        'apprenant_id',
        'type_document',
        'numero_document',
        'formation',
        'module_suivi',
        'date_delivrance',
        'annee_academique',
        'fichier_pdf',
        'statut',
        'date_retrait'
    ];

    protected $casts = [
    'date_delivrance' => 'date',
    'date_retrait' => 'date',
];
    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }
}