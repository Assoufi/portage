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
        'total_ht',
        'tva',
        'montant_ttc',
    ];

    protected $casts = [
        'quantite'       => 'integer',
        'prix_unitaire'  => 'float',
        'total_ht'       => 'float',
        'tva'            => 'float',
        'montant_ttc'    => 'float',
    ];

    protected $attributes = [
        'quantite' => 1,
        'tva'      => 20.00,
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

    public function setTotalHtAttribute($value): void
    {
        $this->attributes['total_ht'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function setTvaAttribute($value): void
    {
        $this->attributes['tva'] = ($value === '' || $value === null) ? 20 : $value;
    }

    public function setMontantTtcAttribute($value): void
    {
        $this->attributes['montant_ttc'] = ($value === '' || $value === null) ? 0 : $value;
    }

    public function getMontantTtcFormateAttribute(): string
    {
        return number_format($this->montant_ttc ?? 0, 2, ',', ' ');
    }

    public function getTotalHtFormateAttribute(): string
    {
        return number_format($this->total_ht ?? 0, 2, ',', ' ');
    }

    public function getPrixUnitaireFormateAttribute(): string
    {
        return number_format($this->prix_unitaire ?? 0, 2, ',', ' ');
    }
}
