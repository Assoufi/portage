<?php

// app/Models/Attestation.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attestations';

    protected $fillable = [
        'consultant_id',
        'mission_id',
        'fonction',
        'date_attestation',
        'date_signature',
        'date_debut',
        'date_fin',
        'client',
    ];

    protected $casts = [
        'date_attestation' => 'date',
        'date_signature' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    // Accesseurs
    public function getDateAttestationFormatteeAttribute(): ?string
    {
        return $this->date_attestation?->format('d/m/Y');
    }

    public function getDateSignatureFormatteeAttribute(): ?string
    {
        return $this->date_signature?->format('d/m/Y');
    }

    public function getDateDebutFormatteeAttribute(): ?string
    {
        return $this->date_debut?->format('d/m/Y');
    }

    public function getDateFinFormatteeAttribute(): ?string
    {
        return $this->date_fin?->format('d/m/Y');
    }

    public function getDureeAttribute(): ?int
    {
        if (! $this->date_debut || ! $this->date_fin) {
            return null;
        }

        return (int) abs($this->date_debut->diffInDays($this->date_fin));
    }

    public function getDureeFormateeAttribute(): string
    {
        $duree = $this->duree;

        if ($duree === null) {
            return 'Non définie';
        }

        return $duree.' jour'.($duree > 1 ? 's' : '');
    }

    public function getMissionLibelleAttribute(): string
    {
        return $this->mission?->titre ?? 'Mission #'.$this->mission_id;
    }

    // Mutateurs
    public function setFonctionAttribute($value)
    {
        $this->attributes['fonction'] = ($value === '' || $value === null) ? $value : trim($value);
    }

    public function setClientAttribute($value)
    {
        $this->attributes['client'] = ($value === '' || $value === null) ? $value : trim($value);
    }

    public function setDateAttestationAttribute($value)
    {
        $this->attributes['date_attestation'] = $value ? Carbon::parse($value) : null;
    }

    public function setDateSignatureAttribute($value)
    {
        $this->attributes['date_signature'] = $value ? Carbon::parse($value) : null;
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
    public function scopeParConsultant($query, $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    public function scopeParMission($query, $missionId)
    {
        return $query->where('mission_id', $missionId);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_attestation', [$debut, $fin]);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where('client', 'LIKE', "%{$terme}%")
            ->orWhere('fonction', 'LIKE', "%{$terme}%");
    }

    // Validation personnalisée
    public static function validateDates($dateDebut, $dateFin): bool
    {
        if (! $dateFin) {
            return true;
        }

        return Carbon::parse($dateDebut)->lte(Carbon::parse($dateFin));
    }
}
