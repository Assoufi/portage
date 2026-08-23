<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailFacture extends Model
{
    protected $table = 'detail_factures';

    protected $fillable = [
        'facture_id',
        'designation',
        'quantite',
        'prix_unitaire',
    ];

    protected $casts = [
        'quantite'       => 'integer',
        'prix_unitaire'  => 'float',
    ];

    protected $attributes = [
        'quantite' => 1,
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function setQuantiteAttribute($value): void
    {
        $this->attributes['quantite'] = ($value === '' || $value === null) ? 1 : (int) $value;
    }

    public function setPrixUnitaireAttribute($value): void
    {
        $this->attributes['prix_unitaire'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function getPrixUnitaireFormateAttribute(): string
    {
        return number_format($this->prix_unitaire ?? 0, 2, ',', ' ');
    }

    public function getTotalHtAttribute(): float
    {
        return ($this->quantite ?? 1) * ($this->prix_unitaire ?? 0);
    }

    public function getTotalHtFormateAttribute(): string
    {
        return number_format($this->total_ht ?? 0, 2, ',', ' ');
    }

    public function getTvaAttribute(): float
    {
        $tvaRate = $this->facture?->client?->tva ?? 20;
        return $this->total_ht * $tvaRate / 100;
    }

    public function getTvaFormateAttribute(): string
    {
        return number_format($this->tva ?? 0, 2, ',', ' ');
    }

    public function getMontantTtcAttribute(): float
    {
        return $this->total_ht + $this->tva;
    }

    public function getMontantTtcFormateAttribute(): string
    {
        return number_format($this->montant_ttc ?? 0, 2, ',', ' ');
    }
}
