<?php
// app/Models/Mission.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Mission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'missions';

    protected $fillable = [
        'consultant_id',
        'client_id',
        'fournisseur_id',
        'titre',
        'formule',
        'taux',
        'tjm',
        'prix_vente',
        'date_debut',
        'date_fin',
        'delai_paiement',
        'remarques'
    ];

    protected $casts = [
        'taux' => 'float',
        'tjm' => 'float',
        'prix_vente' => 'float',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'delai_paiement' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relations
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class, 'mission_id');
    }

    // Accesseurs
    public function getDureeAttribute(): ?int
    {
        if (!$this->date_fin) {
            return null;
        }
        return $this->date_debut->diffInDays($this->date_fin);
    }

    public function getDureeFormattedAttribute(): string
    {
        if (!$this->date_fin) {
            return 'En cours';
        }
        $duree = $this->duree;
        return $duree . ' jour' . ($duree > 1 ? 's' : '');
    }

    public function getStatutAttribute(): string
    {
        if (!$this->date_fin) {
            return 'En cours';
        }
        
        if ($this->date_fin->isPast()) {
            return 'Terminée';
        }
        
        return 'Planifiée';
    }

    public function getStatutBadgeAttribute(): string
    {
        return match($this->statut) {
            'En cours' => '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">En cours</span>',
            'Terminée' => '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Terminée</span>',
            'Planifiée' => '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full">Planifiée</span>',
            default => '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">Non défini</span>',
        };
    }

    public function getMargeAttribute(): float
    {
        return $this->prix_vente - ($this->tjm * ($this->taux ?? 0));
    }

    public function getMargeFormattedAttribute(): string
    {
        return number_format($this->marge, 2) . ' ' . ($this->client->devise ?? 'MAD');
    }

    public function getDatePaiementAttribute(): ?Carbon
    {
        if (!$this->date_fin) {
            return null;
        }
        return $this->date_fin->copy()->addDays($this->delai_paiement);
    }

    // Accesseurs formule
    public function getFormuleFormattedAttribute(): string
    {
        return $this->formule ?? 'Non définie';
    }

    // Mutateurs
    public function setTitreAttribute($value)
    {
        $this->attributes['titre'] = ($value === '' || $value === null) ? null : trim($value);
    }

    public function setFormuleAttribute($value)
    {
        $this->attributes['formule'] = ($value === '' || $value === null) ? null : trim($value);
    }

    public function setTauxAttribute($value)
    {
        $this->attributes['taux'] = ($value === '') ? null : $value;
    }

    public function setTjmAttribute($value)
    {
        $this->attributes['tjm'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setPrixVenteAttribute($value)
    {
        $this->attributes['prix_vente'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setDateDebutAttribute($value)
    {
        $this->attributes['date_debut'] = $value ? Carbon::parse($value) : null;
    }

    public function setDateFinAttribute($value)
    {
        $this->attributes['date_fin'] = $value ? Carbon::parse($value) : null;
    }

    // Scopes
    public function scopeEnCours($query)
    {
        return $query->where(function ($q) {
                $q->whereNull('date_debut')
                  ->orWhere('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('date_fin')
                  ->orWhere('date_fin', '>', now());
            });
    }

    public function scopeTerminees($query)
    {
        return $query->where(function ($q) {
                $q->whereNotNull('date_debut')
                  ->where('date_debut', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNotNull('date_fin')
                  ->where('date_fin', '<', now());
            });
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_debut', [$debut, $fin]);
    }

    public function scopeParConsultant($query, $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // Validation personnalisée
    public static function validateDates($dateDebut, $dateFin = null): bool
    {
        if (!$dateFin) {
            return true;
        }
        return Carbon::parse($dateDebut)->lte(Carbon::parse($dateFin));
    }
}