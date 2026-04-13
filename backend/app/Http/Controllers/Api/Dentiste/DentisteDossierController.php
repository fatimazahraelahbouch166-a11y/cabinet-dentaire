<?php

namespace App\Http\Controllers\Api\Dentiste;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationActe;
use App\Models\DossierMedical;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DentisteDossierController extends Controller
{
    /**
     * GET /api/dentiste/dossiers
     * Liste des patients avec leur dossier (résumé)
     */
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::with(['user', 'dossierMedical'])
            ->actifs()
            ->when($request->search, fn($q) => $q->search($request->search))
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $patients]);
    }

    /**
     * GET /api/dentiste/dossiers/{patientId}
     * Dossier complet d'un patient
     */
    public function show(Request $request, int $patientId): JsonResponse
    {
        $patient = Patient::with([
            'user',
            'dossierMedical.consultations.actes.catalogueActe',
            'dossierMedical.consultations.dentiste:id,name',
            'plansTraitement.seances',
            'ordonnances' => fn($q) => $q->where('is_archived', false)->orderByDesc('date_ordonnance'),
        ])->find($patientId);

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $patient]);
    }

    /**
     * PUT /api/dentiste/dossiers/{patientId}
     * Modifier le dossier médical d'un patient
     */
    public function update(Request $request, int $patientId): JsonResponse
    {
        $patient = Patient::find($patientId);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $request->validate([
            'groupe_sanguin'        => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'antecedents_medicaux'  => 'nullable|string',
            'antecedents_dentaires' => 'nullable|string',
            'allergies'             => 'nullable|string',
            'medicaments_en_cours'  => 'nullable|string',
            'notes_generales'       => 'nullable|string',
        ]);

        $dossier = DossierMedical::firstOrCreate(
            ['patient_id' => $patient->id],
        );

        $dossier->update($request->only([
            'groupe_sanguin', 'antecedents_medicaux', 'antecedents_dentaires',
            'allergies', 'medicaments_en_cours', 'notes_generales',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $dossier->fresh(),
            'message' => 'Dossier médical mis à jour.',
        ]);
    }

    // ── Consultations ─────────────────────────────────────────────────────────

    /**
     * POST /api/dentiste/dossiers/{patientId}/consultations
     */
    public function storeConsultation(Request $request, int $patientId): JsonResponse
    {
        $patient = Patient::find($patientId);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $request->validate([
            'rendez_vous_id'   => 'nullable|exists:rendez_vous,id',
            'date_consultation'=> 'required|date',
            'motif'            => 'required|string|max:255',
            'diagnostic'       => 'nullable|string',
            'notes'            => 'nullable|string',
            'observations'     => 'nullable|string',
            'actes'            => 'nullable|array',
            'actes.*.catalogue_acte_id' => 'required|exists:catalogue_actes,id',
            'actes.*.quantite'          => 'required|integer|min:1',
            'actes.*.prix_unitaire'     => 'required|numeric|min:0',
            'actes.*.dent'              => 'nullable|string|max:10',
            'actes.*.notes'             => 'nullable|string',
        ]);

        $dossier = DossierMedical::firstOrCreate(['patient_id' => $patient->id]);

        $consultation = Consultation::create([
            'dossier_medical_id' => $dossier->id,
            'rendez_vous_id'     => $request->rendez_vous_id,
            'dentiste_id'        => $request->user()->id,
            'date_consultation'  => $request->date_consultation,
            'motif'              => $request->motif,
            'diagnostic'         => $request->diagnostic,
            'notes'              => $request->notes,
            'observations'       => $request->observations,
        ]);

        // Enregistrer les actes réalisés
        if ($request->actes) {
            foreach ($request->actes as $acte) {
                ConsultationActe::create([
                    'consultation_id'   => $consultation->id,
                    'catalogue_acte_id' => $acte['catalogue_acte_id'],
                    'quantite'          => $acte['quantite'],
                    'prix_unitaire'     => $acte['prix_unitaire'],
                    'dent'              => $acte['dent'] ?? null,
                    'notes'             => $acte['notes'] ?? null,
                ]);
            }
        }

        // Marquer le RDV comme terminé si lié
        if ($request->rendez_vous_id) {
            \App\Models\RendezVous::where('id', $request->rendez_vous_id)
                ->update(['statut' => 'termine']);
        }

        return response()->json([
            'success' => true,
            'data'    => $consultation->load('actes.catalogueActe'),
            'message' => 'Consultation enregistrée.',
        ], 201);
    }

    /**
     * PUT /api/dentiste/dossiers/{patientId}/consultations/{consultationId}
     */
    public function updateConsultation(Request $request, int $patientId, int $consultationId): JsonResponse
    {
        $consultation = Consultation::where('id', $consultationId)
            ->whereHas('dossierMedical', fn($q) => $q->where('patient_id', $patientId))
            ->first();

        if (!$consultation) {
            return response()->json(['success' => false, 'message' => 'Consultation introuvable.'], 404);
        }

        $request->validate([
            'diagnostic'   => 'nullable|string',
            'notes'        => 'nullable|string',
            'observations' => 'nullable|string',
        ]);

        $consultation->update($request->only(['diagnostic', 'notes', 'observations']));

        return response()->json([
            'success' => true,
            'data'    => $consultation->fresh()->load('actes.catalogueActe'),
            'message' => 'Consultation mise à jour.',
        ]);
    }
}