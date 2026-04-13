<?php

namespace App\Http\Controllers\Api\Dentiste;

use App\Http\Controllers\Controller;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DentistePlanningController extends Controller
{
    /**
     * GET /api/dentiste/planning
     * Planning journalier ou hebdomadaire selon le paramètre ?vue=jour|semaine&date=YYYY-MM-DD
     */
    public function index(Request $request): JsonResponse
    {
        $dentiste = $request->user();
        $vue      = $request->vue ?? 'jour';
        $date     = $request->date ? Carbon::parse($request->date) : today();

        if ($vue === 'semaine') {
            $debut = $date->copy()->startOfWeek();
            $fin   = $date->copy()->endOfWeek();

            $rdvs = RendezVous::with(['patient.user'])
                ->where('dentiste_id', $dentiste->id)
                ->whereBetween('date_heure', [$debut->startOfDay(), $fin->endOfDay()])
                ->orderBy('date_heure')
                ->get();

            // Grouper par jour
            $grouped = $rdvs->groupBy(fn($rdv) => Carbon::parse($rdv->date_heure)->toDateString());

            return response()->json([
                'success' => true,
                'data'    => [
                    'vue'        => 'semaine',
                    'debut'      => $debut->toDateString(),
                    'fin'        => $fin->toDateString(),
                    'planning'   => $grouped,
                ],
            ]);
        }

        // Vue journalière
        $rdvs = RendezVous::with(['patient.user', 'patient.dossierMedical'])
            ->where('dentiste_id', $dentiste->id)
            ->whereDate('date_heure', $date)
            ->orderBy('date_heure')
            ->get()
            ->map(fn($rdv) => $this->formatRdvAvecPatient($rdv));

        return response()->json([
            'success' => true,
            'data'    => [
                'vue'     => 'jour',
                'date'    => $date->toDateString(),
                'total'   => $rdvs->count(),
                'rdvs'    => $rdvs,
            ],
        ]);
    }

    /**
     * GET /api/dentiste/planning/{id}
     * Détail d'un RDV avec antécédents et allergies du patient
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dentiste = $request->user();

        $rdv = RendezVous::with([
            'patient.user',
            'patient.dossierMedical',
            'patient.dossierMedical.consultations' => fn($q) => $q->orderByDesc('date_consultation')->limit(5),
        ])
            ->where('dentiste_id', $dentiste->id)
            ->find($id);

        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'RDV introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $rdv]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private function formatRdvAvecPatient(RendezVous $rdv): array
    {
        $patient = $rdv->patient;
        $dossier = $patient?->dossierMedical;

        return [
            'id'            => $rdv->id,
            'date_heure'    => $rdv->date_heure,
            'duree_minutes' => $rdv->duree_minutes,
            'motif'         => $rdv->motif,
            'statut'        => $rdv->statut,
            'is_urgence'    => $rdv->is_urgence,
            'notes'         => $rdv->notes,
            'patient'       => $patient ? [
                'id'           => $patient->id,
                'name'         => $patient->user?->name,
                'telephone'    => $patient->telephone,
                'allergies'    => $dossier?->allergies,
                'antecedents'  => $dossier?->antecedents_medicaux,
            ] : null,
        ];
    }
}