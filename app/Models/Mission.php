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
        'taux',
        'tjm',
        'prix_vente',
        'date_debut',
        'date_fin',
        'delai_paiement',
        'remarques'
    ];

    protected $casts = [
        'taux' => 'decimal:2',
        'tjm' => 'decimal:2',
        'prix_vente' => 'decimal:2',
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
        return $this->prix_vente - ($this->tjm * $this->taux);
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

    // Mutateurs
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
        return $query->whereNull('date_fin')
                     ->orWhere('date_fin', '>=', now());
    }

    public function scopeTerminees($query)
    {
        return $query->whereNotNull('date_fin')
                     ->where('date_fin', '<', now());
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