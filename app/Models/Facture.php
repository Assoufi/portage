<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use HasFactory, SoftDeletes;

    protected $table      = 'factures';
    protected $primaryKey = 'id';

    protected $fillable = [
        'fournisseur_id',
        'client_id',
        'numero_facture',
        'numero_bcm',
        'date_facture',
        'designation',
        'quantite',
        'prix_unitaire',
        'total_ht',
        'tva',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference_paiement',
        'date_echeance',
        'remarques',
        'date_reception',
        'date_reglement',
        'mode_reglement',
        'reference_reglement',
        'beneficiaire',
        'statut',
    ];

    protected $casts = [
        'date_facture'        => 'date:Y-m-d',
        'date_paiement'       => 'date:Y-m-d',
        'date_echeance'       => 'date:Y-m-d',
        'date_reception'      => 'date:Y-m-d',
        'date_reglement'      => 'date:Y-m-d',
        'montant'             => 'decimal:2',
        'total_ht'            => 'decimal:2',
        'tva'                 => 'decimal:2',
        'quantite'            => 'integer',
        'prix_unitaire'       => 'decimal:2',
        'statut'              => 'boolean',
    ];

    protected $attributes = [
        'statut' => true,
        'tva'    => 20.00,
    ];

    public function details(): HasMany
    {
        return $this->hasMany(DetailFacture::class, 'facture_id');
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getIsRegleeAttribute(): bool
    {
        return ! is_null($this->date_reglement);
    }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant ?? 0, 2, ',', ' ');
    }

    public function getTotalHtFormateAttribute(): string
    {
        return number_format($this->total_ht ?? 0, 2, ',', ' ');
    }

    public function getDateFactureFormateeAttribute(): string
    {
        return $this->date_facture?->format('d/m/Y') ?? '-';
    }

    public function getDateEcheanceFormateeAttribute(): string
    {
        return $this->date_echeance?->format('d/m/Y') ?? '-';
    }

    public function getDateReglementFormateeAttribute(): string
    {
        return $this->date_reglement?->format('d/m/Y') ?? '-';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut ? 'Active' : 'Inactive';
    }

    public function getStatutBadgeAttribute(): string
    {
        if ($this->is_reglee) {
            return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Réglée</span>';
        }

        if ($this->date_echeance && $this->date_echeance->isPast()) {
            return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">En retard</span>';
        }

        return $this->statut
            ? '<span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-200 rounded-full">En attente</span>'
            : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Inactive</span>';
    }

    public function setNumeroFactureAttribute($value): void
    {
        $this->attributes['numero_facture'] = strtoupper(trim($value));
    }

    public function setBeneficiaireAttribute($value): void
    {
        $this->attributes['beneficiaire'] = $value ? ucwords(strtolower(trim($value))) : null;
    }

    public function setRemarquesAttribute($value): void
    {
        $this->attributes['remarques'] = $value ? trim($value) : null;
    }

    public static function genererNumeroFacture(): string
    {
        $prefixe = 'FACT-' . date('Y') . '-';
        $dernier = static::withTrashed()
            ->where('numero_facture', 'LIKE', $prefixe . '%')
            ->orderBy('id', 'desc')
            ->value('numero_facture');

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

    public function scopeNonReglee($query)
    {
        return $query->whereNull('date_reglement');
    }

    public function scopeReglee($query)
    {
        return $query->whereNotNull('date_reglement');
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_facture', [$debut, $fin]);
    }

    public function scopeParEcheance($query, $debut, $fin)
    {
        return $query->whereBetween('date_echeance', [$debut, $fin]);
    }

    public function scopeParFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('numero_facture', 'LIKE', "%{$terme}%")
            ->orWhere('designation', 'LIKE', "%{$terme}%")
            ->orWhere('beneficiaire', 'LIKE', "%{$terme}%")
            ->orWhereHas('client', fn($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('fournisseur', fn($q) => $q->where('nom', 'LIKE', "%{$terme}%"));
    }
}
