<?php
// app/Models/Consultant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ModePaiement;

class Consultant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultants';

    protected $fillable = [
        'nom',
        'email',
        'tel',
        'rib',
        'mode_paiement',
        'statut'
    ];

    protected $casts = [
        'statut' => 'boolean',
        'mode_paiement' => ModePaiement::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'statut' => true,
        'mode_paiement' => 'virement'
    ];

    // Relations
    public function missions()
    {
        return $this->hasMany(Mission::class);
    }

    public function missionsActives()
    {
        return $this->missions()->whereNull('date_fin')
                               ->orWhere('date_fin', '>=', now());
    }

    // Accesseurs
    public function getNomCompletAttribute(): string
    {
        return $this->nom;
    }

    public function getModePaiementLabelAttribute(): string
    {
        return $this->mode_paiement?->label() ?? 'Non défini';
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

    // Mutateurs
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim($value));
    }

    public function setNomAttribute($value)
    {
        $this->attributes['nom'] = ucwords(strtolower(trim($value)));
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', true);
    }

    public function scopeInactif($query)
    {
        return $query->where('statut', false);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('nom', 'LIKE', "%{$terme}%")
                     ->orWhere('email', 'LIKE', "%{$terme}%")
                     ->orWhere('tel', 'LIKE', "%{$terme}%");
    }
}