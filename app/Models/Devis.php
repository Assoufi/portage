<?php

// app/Models/Devis.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'devis';

    protected $fillable = [
        'fournisseur_id',
        'client_id',
        'mission_id',
        'numero_devis',
        'date_devis',
        'description',
        'quantite',
        'prix_unitaire',
    ];

    protected $casts = [
        'date_devis' => 'date',
        'quantite' => 'float',
        'prix_unitaire' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'quantite' => 1,
        'prix_unitaire' => 0,
    ];

    // Relations
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    // Accesseurs
    public function getTotalHtAttribute(): float
    {
        return ($this->quantite ?? 0) * ($this->prix_unitaire ?? 0);
    }

    public function getTotalHtFormateAttribute(): string
    {
        return number_format($this->total_ht, 2, ',', ' ');
    }

    public function getMontantTvaAttribute(): float
    {
        $taux = $this->client?->tva ?? 20;

        return $this->total_ht * $taux / 100;
    }

    public function getMontantTvaFormateAttribute(): string
    {
        return number_format($this->montant_tva, 2, ',', ' ');
    }

    public function getMontantTtcAttribute(): float
    {
        return $this->total_ht + $this->montant_tva;
    }

    public function getMontantTtcFormateAttribute(): string
    {
        return number_format($this->montant_ttc, 2, ',', ' ');
    }

    public function getDateDevisFormateeAttribute(): ?string
    {
        return $this->date_devis?->format('d/m/Y');
    }

    public function getMissionLibelleAttribute(): string
    {
        return $this->mission?->titre ?? 'Sans mission';
    }

    public function getDeviseAttribute(): string
    {
        return $this->client?->devise ?? 'MAD';
    }

    // Mutateurs
    public function setNumeroDevisAttribute($value): void
    {
        $this->attributes['numero_devis'] = $value ? strtoupper(trim($value)) : $value;
    }

    public function setDescriptionAttribute($value): void
    {
        $trimmed = is_string($value) ? trim($value) : $value;
        $this->attributes['description'] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
    }

    public function setQuantiteAttribute($value): void
    {
        $this->attributes['quantite'] = ($value === '' || $value === null) ? 1 : $value;
    }

    public function setPrixUnitaireAttribute($value): void
    {
        $this->attributes['prix_unitaire'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setDateDevisAttribute($value): void
    {
        $this->attributes['date_devis'] = $value ? Carbon::parse($value) : null;
    }

    /**
     * Génère le prochain numéro de devis au format "DEV-yyyy-0001".
     * Incrémente le maximum existant (+ soft-deleted, à cause de la contrainte UNIQUE).
     */
    public static function genererNumeroDevis(): string
    {
        $prefixe = 'DEV-'.date('Y').'-';

        $dernier = static::withTrashed()
            ->where('numero_devis', 'LIKE', $prefixe.'%')
            ->orderBy('id', 'desc')
            ->value('numero_devis');

        $numero = $dernier ? (int) substr($dernier, strlen($prefixe)) + 1 : 1;

        return $prefixe.str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    // Scopes
    public function scopeParClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeParMission($query, $missionId)
    {
        return $query->where('mission_id', $missionId);
    }

    public function scopeParFournisseur($query, $fournisseurId)
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_devis', [$debut, $fin]);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('numero_devis', 'LIKE', "%{$terme}%")
            ->orWhere('description', 'LIKE', "%{$terme}%")
            ->orWhereHas('client', fn ($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('fournisseur', fn ($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('mission', fn ($q) => $q->where('titre', 'LIKE', "%{$terme}%"));
    }
}
