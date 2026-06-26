<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ModePaiement;

class Paiement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paiements';

    protected $fillable = [
        'client_id',
        'fournisseur_id',
        'mission_id',
        'reference',
        'montant',
        'montant_recu',
        'date_paiement',
        'mode_paiement',
        'remarques',
        'statut',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'montant_recu' => 'decimal:2',
        'date_paiement' => 'date',
        'mode_paiement' => ModePaiement::class,
        'statut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'statut' => true,
        'mode_paiement' => 'virement',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function repartitions()
    {
        return $this->hasMany(Repartition::class);
    }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2);
    }

    public function getMontantRecuFormateAttribute(): string
    {
        return $this->montant_recu ? number_format($this->montant_recu, 2) : '-';
    }

    public function getSoldeRestantAttribute(): ?float
    {
        if ($this->montant_recu === null) return null;
        return $this->montant - $this->montant_recu;
    }

    public function getSoldeRestantFormateAttribute(): string
    {
        $solde = $this->solde_restant;
        return $solde !== null ? number_format($solde, 2) : '-';
    }

    public function getTotalRepartiAttribute(): float
    {
        return (float) $this->repartitions()->sum('montant');
    }

    public function getTotalRepartiFormateAttribute(): string
    {
        return number_format($this->total_reparti, 2);
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

    public function getModePaiementLabelAttribute(): string
    {
        return $this->mode_paiement?->label() ?? 'Non défini';
    }

    public function getDatePaiementFormateeAttribute(): string
    {
        return $this->date_paiement?->format('d/m/Y') ?? '-';
    }

    public function getCreatedAtFormateeAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function setReferenceAttribute($value)
    {
        $this->attributes['reference'] = strtoupper(trim($value));
    }

    public function setRemarquesAttribute($value)
    {
        $this->attributes['remarques'] = $value ? trim($value) : null;
    }

    public static function genererReference(): string
    {
        $prefixe = 'PAY-' . date('Y') . '-';
        $dernier = static::withTrashed()
            ->where('reference', 'LIKE', $prefixe . '%')
            ->orderBy('id', 'desc')
            ->value('reference');

        if ($dernier) {
            $numero = (int) substr($dernier, strlen($prefixe)) + 1;
        } else {
            $numero = 1;
        }

        return $prefixe . str_pad($numero, 5, '0', STR_PAD_LEFT);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', true);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_paiement', [$debut, $fin]);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeParFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeParMission($query, $missionId)
    {
        return $query->where('mission_id', $missionId);
    }

    public function scopeParModePaiement($query, $mode)
    {
        return $query->where('mode_paiement', $mode);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('reference', 'LIKE', "%{$terme}%")
            ->orWhereHas('client', fn($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('fournisseur', fn($q) => $q->where('nom', 'LIKE', "%{$terme}%"));
    }
}
