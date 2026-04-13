<?php

namespace App\Http\Controllers\Api\Dentiste;

use App\Http\Controllers\Controller;
use App\Models\PlanTraitement;
use App\Models\SeanceTraitement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DentistePlanTraitementController extends Controller
{
    /**
     * GET /api/dentiste/plans-traitement
     */
    public function index(Request $request): JsonResponse
    {
        $dentiste = $request->user();

        $plans = PlanTraitement::with(['patient.user', 'seances'])
            ->where('dentiste_id', $dentiste->id)
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->statut, fn($q) => $q->where('statut', $request->statut))
            ->orderByDesc('date_debut')
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $plans]);
    }

    /**
     * POST /api/dentiste/plans-traitement
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'titre'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'date_debut'         => 'required|date',
            'date_fin_prevue'    => 'nullable|date|after_or_equal:date_debut',
            'cout_total_estime'  => 'nullable|numeric|min:0',
            'seances'            => 'nullable|array',
            'seances.*.objectif' => 'required|string|max:255',
            'seances.*.notes'    => 'nullable|string',
        ]);

        $plan = PlanTraitement::create([
            'patient_id'        => $request->patient_id,
            'dentiste_id'       => $request->user()->id,
            'titre'             => $request->titre,
            'description'       => $request->description,
            'statut'            => 'en_cours',
            'date_debut'        => $request->date_debut,
            'date_fin_prevue'   => $request->date_fin_prevue,
            'cout_total_estime' => $request->cout_total_estime ?? 0,
        ]);

        if ($request->seances) {
            foreach ($request->seances as $i => $seance) {
                SeanceTraitement::create([
                    'plan_traitement_id' => $plan->id,
                    'numero_seance'      => $i + 1,
                    'objectif'           => $seance['objectif'],
                    'statut'             => 'planifiee',
                    'notes'              => $seance['notes'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $plan->load(['patient.user', 'seances']),
            'message' => 'Plan de traitement créé.',
        ], 201);
    }

    /**
     * GET /api/dentiste/plans-traitement/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $plan = PlanTraitement::with(['patient.user', 'seances.consultation'])
            ->where('dentiste_id', $request->user()->id)
            ->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $plan]);
    }

    /**
     * PUT /api/dentiste/plans-traitement/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = PlanTraitement::where('dentiste_id', $request->user()->id)->find($id);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan introuvable.'], 404);
        }

        $request->validate([
            'titre'             => 'sometimes|string|max:255',
            'description'       => 'nullable|string',
            'statut'            => 'sometimes|in:en_cours,termine,abandonne',
            'date_fin_prevue'   => 'nullable|date',
            'cout_total_estime' => 'nullable|numeric|min:0',
        ]);

        $plan->update($request->only(['titre', 'description', 'statut', 'date_fin_prevue', 'cout_total_estime']));

        return response()->json([
            'success' => true,
            'data'    => $plan->fresh()->load('seances'),
            'message' => 'Plan de traitement mis à jour.',
        ]);
    }

    /**
     * PATCH /api/dentiste/plans-traitement/{planId}/seances/{seanceId}
     * Mettre à jour le statut d'une séance
     */
    public function updateSeance(Request $request, int $planId, int $seanceId): JsonResponse
    {
        $plan = PlanTraitement::where('dentiste_id', $request->user()->id)->find($planId);

        if (!$plan) {
            return response()->json(['success' => false, 'message' => 'Plan introuvable.'], 404);
        }

        $seance = SeanceTraitement::where('plan_traitement_id', $planId)->find($seanceId);

        if (!$seance) {
            return response()->json(['success' => false, 'message' => 'Séance introuvable.'], 404);
        }

        $request->validate([
            'statut'         => 'required|in:planifiee,realisee,annulee',
            'consultation_id'=> 'nullable|exists:consultations,id',
            'notes'          => 'nullable|string',
        ]);

        $seance->update($request->only(['statut', 'consultation_id', 'notes']));

        // Si toutes les séances sont réalisées ou annulées → terminer le plan
        $toutTermine = $plan->seances()->whereNotIn('statut', ['realisee', 'annulee'])->doesntExist();
        if ($toutTermine) {
            $plan->update(['statut' => 'termine']);
        }

        return response()->json([
            'success' => true,
            'data'    => $seance->fresh(),
            'message' => 'Séance mise à jour.',
        ]);
    }
}