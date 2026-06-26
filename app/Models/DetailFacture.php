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
        'prix_unitaire'  => 'decimal:2',
        'total_ht'       => 'decimal:2',
        'tva'            => 'decimal:2',
        'montant_ttc'    => 'decimal:2',
    ];

    protected $attributes = [
        'quantite' => 1,
        'tva'      => 20.00,
    ];

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class, 'facture_id');
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
