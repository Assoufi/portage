<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Avoir extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'avoirs';

    protected $fillable = [
        'facture_id',
        'fournisseur_id',
        'client_id',
        'consultant_id',
        'mission_id',
        'numero_avoir',
        'date_avoir',
        'type',
        'motif',
        'total_ht',
        'taux_tva',
        'montant_ttc',
        'remarques',
        'statut',
    ];

    protected $casts = [
        'date_avoir' => 'date:Y-m-d',
        'total_ht' => 'float',
        'taux_tva' => 'float',
        'montant_ttc' => 'float',
        'statut' => 'boolean',
    ];

    protected $attributes = [
        'statut' => true,
        'taux_tva' => 20.00,
        'type' => 'partiel',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(DetailAvoir::class, 'avoir_id');
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function calculateTotals(): void
    {
        $this->loadMissing('details');
        $totalHt = $this->details->sum('montant_ht');
        $tva = $totalHt * (float) ($this->taux_tva ?? 20) / 100;

        $this->total_ht = $totalHt;
        $this->montant_ttc = $totalHt + $tva;
    }

    /**
     * Génère le prochain numéro d'avoir au format "AV-yyyy-0001".
     */
    public static function genererNumeroAvoir(): string
    {
        $prefixe = 'AV-'.date('Y').'-';

        $dernier = static::withTrashed()
            ->where('numero_avoir', 'LIKE', $prefixe.'%')
            ->orderBy('id', 'desc')
            ->value('numero_avoir');

        $numero = $dernier ? (int) substr($dernier, strlen($prefixe)) + 1 : 1;

        return $prefixe.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    public function getDateAvoirFormateeAttribute(): string
    {
        return $this->date_avoir?->format('d/m/Y') ?? '-';
    }

    public function getTotalHtFormateAttribute(): string
    {
        return number_format($this->total_ht ?? 0, 2, ',', ' ');
    }

    public function getMontantTtcFormateAttribute(): string
    {
        return number_format($this->montant_ttc ?? 0, 2, ',', ' ');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'total' ? 'Annulation totale' : 'Annulation partielle';
    }

    public function getStatutBadgeAttribute(): string
    {
        if (! $this->statut) {
            return '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">Inactif</span>';
        }

        return $this->type === 'total'
            ? '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Totale</span>'
            : '<span class="px-2 py-1 text-xs font-semibold text-amber-800 bg-amber-200 rounded-full">Partielle</span>';
    }

    public function setNumeroAvoirAttribute($value): void
    {
        if (! empty($value)) {
            $this->attributes['numero_avoir'] = strtoupper(trim($value));
        }
    }

    public function setMotifAttribute($value): void
    {
        $this->attributes['motif'] = $value ? trim($value) : null;
    }

    public function setRemarquesAttribute($value): void
    {
        $this->attributes['remarques'] = $value ? trim($value) : null;
    }

    public function setMontantTtcAttribute($value): void
    {
        $this->attributes['montant_ttc'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setTotalHtAttribute($value): void
    {
        $this->attributes['total_ht'] = ($value === '' || $value === null) ? null : $value;
    }

    public function setTauxTvaAttribute($value): void
    {
        $this->attributes['taux_tva'] = ($value === '' || $value === null) ? 20 : $value;
    }

    public function scopeActif($query)
    {
        return $query->where('statut', true);
    }

    public function scopeParFacture($query, $factureId)
    {
        return $query->where('facture_id', $factureId);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeParFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_avoir', [$debut, $fin]);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('numero_avoir', 'LIKE', "%{$terme}%")
            ->orWhere('motif', 'LIKE', "%{$terme}%")
            ->orWhereHas('facture', fn ($q) => $q->where('numero_facture', 'LIKE', "%{$terme}%"))
            ->orWhereHas('client', fn ($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('fournisseur', fn ($q) => $q->where('nom', 'LIKE', "%{$terme}%"));
    }
}
