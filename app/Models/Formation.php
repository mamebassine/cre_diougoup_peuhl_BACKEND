<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    protected $fillable = [
        'nom',
        'resume',
        'description',
        'duree',
        'diplome',
        'lieu',
        'objectifs',
        'icone',
        'capacite',
        'is_active',
    ];

    protected $casts = [
        'objectifs' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Une formation possède plusieurs inscriptions.
     */
    public function inscriptions()
    {
        return $this->hasMany(Inscription::class);
    }
}