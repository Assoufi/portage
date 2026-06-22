<?php
// app/Models/Client.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'nom',       
        'adresse',
        'email',
        'ice',
        'tva',
        'devise',
        'statut'
    ];

    protected $casts = [
        'statut' => 'boolean',
        'tva' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'statut' => true,
        'tva' => 20.00,
        'devise' => 'MAD'
    ];

    // Relations
    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    // Accesseurs
    public function getNomCompletAttribute(): string
    {
        return $this->nom;
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut ? 'Actif' : 'Inactif';
    }

    public function getStatutBadgeAttribute(): string
    {
        return $this->statut 
            ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Actif</span>'
            : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Inactif</span>';
    }

    public function getTvaFormattedAttribute(): string
    {
        return number_format($this->tva, 2) . ' %';
    }

    // Mutateurs
    public function setNomAttribute($value)
    {
        $this->attributes['nom'] = ucwords(strtolower(trim($value)));
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function setIceAttribute($value)
    {
        $this->attributes['ice'] = strtoupper(trim($value));
    }

    // Validation personnalisée pour ICE
    public static function validateIce($ice): bool
    {
        return strlen($ice) === 15 && preg_match('/^[A-Z0-9]{15}$/', $ice);
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', true);
    }

    public function scopeParDevise($query, $devise)
    {
        return $query->where('devise', $devise);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('nom', 'LIKE', "%{$terme}%")  // AJOUT : Recherche par nom
                     ->orWhere('email', 'LIKE', "%{$terme}%")
                     ->orWhere('ice', 'LIKE', "%{$terme}%")
                     ->orWhere('adresse', 'LIKE', "%{$terme}%");
    }
}