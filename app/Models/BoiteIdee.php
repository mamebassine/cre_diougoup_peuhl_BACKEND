<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoiteIdee extends Model
{
    protected $table = 'boite_idees';

    protected $fillable = [
        'apprenant_id',
        'type_message',
        'objet',
        'message',
        'is_read',
        'statut',
        'reponse',
        'date_reponse'
    ];
    protected $casts = [
    'is_read' => 'boolean',
    'date_reponse' => 'datetime',
];

    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class);
    }
}