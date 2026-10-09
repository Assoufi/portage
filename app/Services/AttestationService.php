<?php

namespace App\Services;

use App\Models\Attestation;
use Illuminate\Support\Facades\DB;

class AttestationService
{
    public function createAttestation(array $data): Attestation
    {
        return DB::transaction(function () use ($data) {
            $attestation = Attestation::create($data);

            return $attestation->fresh();
        });
    }

    public function updateAttestation(Attestation $attestation, array $data): Attestation
    {
        return DB::transaction(function () use ($attestation, $data) {
            $attestation->update($data);

            return $attestation->fresh();
        });
    }

    public function deleteAttestation(Attestation $attestation): void
    {
        DB::transaction(function () use ($attestation) {
            $attestation->delete();
        });
    }

    public function getStats(): array
    {
        $totalAttestations = Attestation::count();
        $attestationsMoisCourant = Attestation::whereMonth('date_attestation', now()->month)
            ->whereYear('date_attestation', now()->year)
            ->count();
        $totalConsultants = Attestation::distinct()->count('consultant_id');
        $dureeMoyenneJours = (int) round(
            Attestation::selectRaw('AVG(DATEDIFF(date_fin, date_debut)) as moyenne')
                ->value('moyenne') ?? 0
        );

        return compact(
            'totalAttestations',
            'attestationsMoisCourant',
            'totalConsultants',
            'dureeMoyenneJours'
        );
    }
}
