<?php
// app/Models/Fournisseur.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fournisseur extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fournisseurs';

    protected $fillable = [
        'nom',
        'adresse',
        'ville',
        'email',
        'responsable',
        'ice',
        'rib',
        'taux',
        'statut'
    ];

    protected $casts = [
        'statut' => 'boolean',
        'taux' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'statut' => true,
        'taux' => 0.00
    ];

    // Relations
    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    // Accesseurs
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

    public function getTauxFormattedAttribute(): string
    {
        return number_format($this->taux, 2) . ' %';
    }

    // Mutateurs
    public function setTauxAttribute($value)
    {
        $this->attributes['taux'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = $value ? strtolower(trim($value)) : null;
    }

    public function setIceAttribute($value)
    {
        $this->attributes['ice'] = strtoupper(trim($value));
    }

    public function setResponsableAttribute($value)
    {
        $this->attributes['responsable'] = $value ? trim($value) : null;
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

    public function scopeRecherche($query, $terme)
    {
        return $query->where('nom', 'LIKE', "%{$terme}%")
                     ->orWhere('email', 'LIKE', "%{$terme}%")
                     ->orWhere('ice', 'LIKE', "%{$terme}%")
                     ->orWhere('adresse', 'LIKE', "%{$terme}%");
    }
}