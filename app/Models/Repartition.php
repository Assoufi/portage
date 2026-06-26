<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ModePaiement;

class Repartition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'repartitions';

    protected $fillable = [
        'paiement_id',
        'consultant_id',
        'montant',
        'date_paiement',
        'remarques',
        'rib',
        'banque',
        'mode_paiement',
        'telephone',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'mode_paiement' => ModePaiement::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'mode_paiement' => 'virement',
    ];

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant, 2);
    }

    public function getDatePaiementFormateeAttribute(): string
    {
        return $this->date_paiement?->format('d/m/Y') ?? '-';
    }

    public function getModePaiementLabelAttribute(): string
    {
        return $this->mode_paiement?->label() ?? 'Non défini';
    }

    public function getCreatedAtFormateeAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function setRemarquesAttribute($value)
    {
        $this->attributes['remarques'] = $value ? trim($value) : null;
    }

    public function setRibAttribute($value)
    {
        $this->attributes['rib'] = $value ? strtoupper(trim($value)) : null;
    }

    public function scopeParPaiement($query, $paiementId)
    {
        return $query->where('paiement_id', $paiementId);
    }

    public function scopeParConsultant($query, $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_paiement', [$debut, $fin]);
    }

    public function scopeParModePaiement($query, $mode)
    {
        return $query->where('mode_paiement', $mode);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->whereHas('consultant', fn($q) => $q->where('nom', 'LIKE', "%{$terme}%"))
            ->orWhereHas('paiement', fn($q) => $q->where('reference', 'LIKE', "%{$terme}%"));
    }
}
