<?php

namespace App\Http\Controllers\Api\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\RendezVous;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretaireRdvController extends Controller
{
    /**
     * GET /api/secretaire/rdv
     * RDV du jour par défaut, ou filtrés par date
     */
    public function index(Request $request): JsonResponse
    {
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : today();

        $rdvs = RendezVous::with(['patient.user', 'dentiste:id,name'])
            ->whereDate('date_heure', $date)
            ->when($request->statut, fn($q) => $q->where('statut', $request->statut))
            ->orderBy('date_heure')
            ->get();

        return response()->json(['success' => true, 'data' => $rdvs]);
    }

    /**
     * GET /api/secretaire/rdv/semaine
     */
    public function semaine(Request $request): JsonResponse
    {
        $debut = $request->debut ? \Carbon\Carbon::parse($request->debut)->startOfWeek() : now()->startOfWeek();
        $fin   = $debut->copy()->endOfWeek();

        $rdvs = RendezVous::with(['patient.user', 'dentiste:id,name'])
            ->whereBetween('date_heure', [$debut, $fin])
            ->orderBy('date_heure')
            ->get();

        return response()->json(['success' => true, 'data' => $rdvs]);
    }

    /**
     * POST /api/secretaire/rdv
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id'    => 'required|exists:patients,id',
            'dentiste_id'   => 'required|exists:users,id',
            'date_heure'    => 'required|date',
            'motif'         => 'required|string|max:255',
            'duree_minutes' => 'nullable|integer|min:15|max:240',
            'is_urgence'    => 'nullable|boolean',
            'notes'         => 'nullable|string',
        ]);

        $conflit = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_heure', $request->date_heure)
            ->whereNotIn('statut', ['annule', 'absent'])
            ->exists();

        if ($conflit) {
            return response()->json(['success' => false, 'message' => 'Créneau déjà occupé.'], 422);
        }

        $rdv = RendezVous::create([
            'patient_id'    => $request->patient_id,
            'dentiste_id'   => $request->dentiste_id,
            'date_heure'    => $request->date_heure,
            'duree_minutes' => $request->duree_minutes ?? 30,
            'motif'         => $request->motif,
            'statut'        => 'confirme',
            'is_urgence'    => $request->is_urgence ?? false,
            'notes'         => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $rdv->load(['patient.user', 'dentiste:id,name']),
            'message' => 'Rendez-vous créé.',
        ], 201);
    }

    /**
     * PUT /api/secretaire/rdv/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $rdv = RendezVous::find($id);
        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'RDV introuvable.'], 404);
        }

        $request->validate([
            'date_heure'    => 'sometimes|date',
            'motif'         => 'sometimes|string|max:255',
            'duree_minutes' => 'nullable|integer|min:15',
            'notes'         => 'nullable|string',
            'is_urgence'    => 'nullable|boolean',
        ]);

        $rdv->update($request->only(['date_heure', 'motif', 'duree_minutes', 'notes', 'is_urgence']));

        return response()->json([
            'success' => true,
            'data'    => $rdv->fresh()->load(['patient.user', 'dentiste:id,name']),
            'message' => 'Rendez-vous mis à jour.',
        ]);
    }

    /**
     * PATCH /api/secretaire/rdv/{id}/statut
     * Changer le statut : confirme → arrive → en_cours → termine / annule / absent
     */
    public function updateStatut(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'statut' => 'required|in:confirme,arrive,en_cours,termine,annule,absent',
        ]);

        $rdv = RendezVous::find($id);
        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'RDV introuvable.'], 404);
        }

        $data = ['statut' => $request->statut];

        if ($request->statut === 'annule') {
            $data['annule_par'] = 'secretaire';
            $data['annule_at']  = now();
        }

        $rdv->update($data);

        return response()->json([
            'success' => true,
            'data'    => $rdv->fresh(),
            'message' => "Statut mis à jour : {$request->statut}.",
        ]);
    }

    /**
     * DELETE /api/secretaire/rdv/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $rdv = RendezVous::find($id);
        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'RDV introuvable.'], 404);
        }

        $rdv->update([
            'statut'     => 'annule',
            'annule_par' => 'secretaire',
            'annule_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'RDV annulé.']);
    }

    /**
     * GET /api/secretaire/dashboard
     */
    public function dashboard(): JsonResponse
    {
        $today = today();

        $rdvsDuJour = RendezVous::with(['patient.user'])
            ->whereDate('date_heure', $today)
            ->orderBy('date_heure')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_rdv_jour'     => $rdvsDuJour->count(),
                'confirmes'          => $rdvsDuJour->where('statut', 'confirme')->count(),
                'arrives'            => $rdvsDuJour->where('statut', 'arrive')->count(),
                'en_cours'           => $rdvsDuJour->where('statut', 'en_cours')->count(),
                'termines'           => $rdvsDuJour->where('statut', 'termine')->count(),
                'urgences'           => $rdvsDuJour->where('is_urgence', true)->count(),
                'rdvs'               => $rdvsDuJour,
                'impayés'            => \App\Models\Facture::whereIn('statut', ['en_attente', 'partiellement_paye'])->count(),
            ],
        ]);
    }
}