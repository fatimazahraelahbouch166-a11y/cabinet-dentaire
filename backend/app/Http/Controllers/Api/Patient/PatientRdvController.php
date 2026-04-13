<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreRdvRequest;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientRdvController extends Controller
{
    /**
     * GET /api/patient/rdv
     * Liste tous les RDV du patient connecté
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Profil patient introuvable.'], 404);
        }

        $rdvs = RendezVous::with(['dentiste:id,name'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('date_heure')
            ->get()
            ->map(fn($rdv) => $this->formatRdv($rdv));

        return response()->json(['success' => true, 'data' => $rdvs]);
    }

    /**
     * POST /api/patient/rdv
     * Créer un nouveau RDV
     */
    public function store(StoreRdvRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Profil patient introuvable.'], 404);
        }

        // Vérifier qu'il n'y a pas déjà un RDV actif sur ce créneau
        $conflit = RendezVous::where('dentiste_id', $request->dentiste_id)
            ->where('date_heure', $request->date_heure)
            ->whereNotIn('statut', ['annule', 'absent'])
            ->exists();

        if ($conflit) {
            return response()->json([
                'success' => false,
                'message' => 'Ce créneau est déjà réservé.',
            ], 422);
        }

        $rdv = RendezVous::create([
            'patient_id'     => $patient->id,
            'dentiste_id'    => $request->dentiste_id,
            'date_heure'     => $request->date_heure,
            'duree_minutes'  => $request->duree_minutes ?? 30,
            'motif'          => $request->motif,
            'statut'         => 'en_attente',
            'is_urgence'     => $request->is_urgence ?? false,
            'notes'          => $request->notes,
        ]);

        $rdv->load('dentiste:id,name');

        return response()->json([
            'success' => true,
            'data'    => $this->formatRdv($rdv),
            'message' => 'Rendez-vous créé avec succès.',
        ], 201);
    }

    /**
     * GET /api/patient/rdv/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $patient = $request->user()->patient;
        $rdv = RendezVous::with(['dentiste:id,name'])
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatRdv($rdv)]);
    }

    /**
     * DELETE /api/patient/rdv/{id}
     * Annuler un RDV (24h minimum avant)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $patient = $request->user()->patient;

        $rdv = RendezVous::where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$rdv) {
            return response()->json(['success' => false, 'message' => 'Rendez-vous introuvable.'], 404);
        }

        if (!$rdv->peutEtreAnnuleParPatient()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous ne peut plus être annulé (moins de 24h ou déjà terminé/annulé).',
            ], 422);
        }

        $rdv->update([
            'statut'     => 'annule',
            'annule_par' => 'patient',
            'annule_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès.',
        ]);
    }

    /**
     * GET /api/patient/creneaux
     * Créneaux disponibles pour un dentiste et une date donnée
     */
    public function creneaux(Request $request): JsonResponse
    {
        $request->validate([
            'dentiste_id' => 'required|exists:users,id',
            'date'        => 'required|date|after_or_equal:today',
        ]);

        $date       = \Carbon\Carbon::parse($request->date);
        $dentisteId = $request->dentiste_id;

        // Récupérer les horaires du jour
        $horaire = \App\Models\Horaire::where('dentiste_id', $dentisteId)
            ->where('jour_semaine', $date->dayOfWeekIso)
            ->where('is_active', true)
            ->first();

        if (!$horaire) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Vérifier congés
        $enConge = \App\Models\Conge::where('dentiste_id', $dentisteId)
            ->where('date_debut', '<=', $date->toDateString())
            ->where('date_fin', '>=', $date->toDateString())
            ->exists();

        if ($enConge) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Générer les créneaux de 30 min
        $debut     = \Carbon\Carbon::parse($date->toDateString() . ' ' . $horaire->heure_debut);
        $fin       = \Carbon\Carbon::parse($date->toDateString() . ' ' . $horaire->heure_fin);
        $creneaux  = [];
        $rdvPris   = RendezVous::where('dentiste_id', $dentisteId)
            ->whereDate('date_heure', $date->toDateString())
            ->whereNotIn('statut', ['annule', 'absent'])
            ->pluck('date_heure')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('H:i'))
            ->toArray();

        $current = $debut->copy();
        while ($current->lt($fin)) {
            $heure = $current->format('H:i');
            if (!in_array($heure, $rdvPris)) {
                $creneaux[] = $heure;
            }
            $current->addMinutes(30);
        }

        return response()->json(['success' => true, 'data' => $creneaux]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────
    private function formatRdv(RendezVous $rdv): array
    {
        return [
            'id'             => $rdv->id,
            'date_heure'     => $rdv->date_heure,
            'duree_minutes'  => $rdv->duree_minutes,
            'motif'          => $rdv->motif,
            'statut'         => $rdv->statut,
            'is_urgence'     => $rdv->is_urgence,
            'notes'          => $rdv->notes,
            'dentiste'       => $rdv->dentiste ? ['id' => $rdv->dentiste->id, 'name' => $rdv->dentiste->name] : null,
            'peut_annuler'   => $rdv->peutEtreAnnuleParPatient(),
        ];
    }
}