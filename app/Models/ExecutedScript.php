<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExecutedScript extends Model
{
    use SoftDeletes;

    protected $table = 'executed_scripts';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'filename',
        'stored_path',
        'checksum',
        'detected_risks',
        'status',
        'output',
        'error_message',
        'duration_ms',
        'affected_rows',
        'ip',
        'executed_at',
    ];

    protected $casts = [
        'detected_risks' => 'array',
        'duration_ms' => 'integer',
        'affected_rows' => 'integer',
        'executed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le fichier source n'est plus intact (modifié/corrompu) : ré-exécution interdite,
     * l'intégrité est revérifiée à chaque exécution via le sha256 du contenu stocké.
     */
    public function getIsTraitableAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatutLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente de confirmation',
            'blocked' => 'Bloqué (requêtes dangereuses)',
            'completed' => 'Exécuté avec succès',
            'failed' => 'Échec d\'exécution',
            default => ucfirst($this->status ?? ''),
        };
    }

    public function getStatutBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">En attente</span>',
            'blocked' => '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Bloqué</span>',
            'completed' => '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Terminé</span>',
            'failed' => '<span class="px-2 py-1 text-xs font-semibold text-orange-800 bg-orange-200 rounded-full">Échec</span>',
            default => '<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">'.$this->status.'</span>',
        };
    }
}
