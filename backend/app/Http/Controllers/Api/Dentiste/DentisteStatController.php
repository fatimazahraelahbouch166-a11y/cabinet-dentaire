<?php

namespace App\Http\Controllers\Api\Dentiste;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationActe;
use App\Models\RendezVous;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DentisteStatController  extends Controller
{
    /**
     * GET /api/dentiste/statistiques
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periode'     => 'nullable|in:semaine,mois,trimestre,annee',
            'date_debut'  => 'nullable|date',
            'date_fin'    => 'nullable|date|after_or_equal:date_debut',
        ]);

        $dentiste  = $request->user();
        $periode   = $request->periode ?? 'mois';

        [$debut, $fin] = $this->getPeriode($request, $periode);

        // Consultations par période
        $consultations = Consultation::where('dentiste_id', $dentiste->id)
            ->whereBetween('date_consultation', [$debut, $fin])
            ->count();

        // Actes réalisés
        $actes = ConsultationActe::whereHas('consultation', fn($q) =>
            $q->where('dentiste_id', $dentiste->id)
              ->whereBetween('date_consultation', [$debut, $fin])
        )->with('catalogueActe:id,libelle,code')
         ->get()
         ->groupBy('catalogue_acte_id')
         ->map(fn($groupe) => [
             'acte'     => $groupe->first()->catalogueActe,
             'quantite' => $groupe->sum('quantite'),
             'total'    => $groupe->sum(fn($a) => $a->quantite * $a->prix_unitaire),
         ])->values();

        // CA généré (sum des actes réalisés)
        $chiffreAffaires = $actes->sum('total');

        // RDV par statut
        $rdvStats = RendezVous::where('dentiste_id', $dentiste->id)
            ->whereBetween('date_heure', [$debut, $fin])
            ->get()
            ->groupBy('statut')
            ->map(fn($g) => $g->count());

        // Consultations par semaine (courbe)
        $consultationsParJour = Consultation::where('dentiste_id', $dentiste->id)
            ->whereBetween('date_consultation', [$debut, $fin])
            ->selectRaw('DATE(date_consultation) as jour, COUNT(*) as total')
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'periode'               => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
                'total_consultations'   => $consultations,
                'chiffre_affaires'      => round($chiffreAffaires, 2),
                'actes_realises'        => $actes,
                'rdv_par_statut'        => $rdvStats,
                'consultations_par_jour'=> $consultationsParJour,
            ],
        ]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private function getPeriode(Request $request, string $periode): array
    {
        if ($request->date_debut && $request->date_fin) {
            return [\Carbon\Carbon::parse($request->date_debut), \Carbon\Carbon::parse($request->date_fin)];
        }

        $fin   = now()->endOfDay();
        $debut = match ($periode) {
            'semaine'   => now()->startOfWeek(),
            'trimestre' => now()->startOfQuarter(),
            'annee'     => now()->startOfYear(),
            default     => now()->startOfMonth(),
        };

        return [$debut, $fin];
    }
}