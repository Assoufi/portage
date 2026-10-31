<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    const STATUTS = ['ACTIF' => 'Actif', 'INACTIF' => 'Inactif'];

    protected $table = 'commandes';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];
    protected $dates = ['date_commande', 'date_debut', 'date_fin', 'created_at', 'updated_at'];

    protected $casts = [
        'date_commande' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'quantite' => 'float',
        'tjm'      => 'float',
    ];

    protected $attributes = [
        'statut' => 'ACTIF',
    ];

    protected $fillable = [
        'mission_id',
        'num_commande',
        'date_commande',
        'date_debut',
        'date_fin',
        'quantite',
        'tjm',
        'statut',
    ];

    public function mission()
    {
        return $this->belongsTo('App\Models\Mission', 'mission_id');
    }
}
