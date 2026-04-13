<?php

namespace App\Http\Controllers\Api\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\DossierMedical;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecretairePatientController extends Controller
{
    /**
     * GET /api/secretaire/patients
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::with('user')
            ->when($request->search, fn($q) => $q->search($request->search))
            ->when($request->has('archived'), fn($q) => $q->where('is_archived', (bool) $request->archived))
            ->when(!$request->has('archived'), fn($q) => $q->where('is_archived', false));

        $patients = $query->orderBy('id', 'desc')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $patients,
        ]);
    }

    /**
     * POST /api/secretaire/patients
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'sometimes|string|min:8',
            'telephone'      => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'sexe'           => 'nullable|in:M,F,autre',
            'adresse'        => 'nullable|string',
            'ville'          => 'nullable|string|max:100',
            'code_postal'    => 'nullable|string|max:10',
            'mutuelle'       => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password ?? 'ChangeMoi123!'),
            'role'     => 'patient',
        ]);

        $patient = Patient::create(array_merge(
            ['user_id' => $user->id],
            $request->only([
                'telephone', 'date_naissance', 'sexe', 'adresse',
                'ville', 'code_postal', 'mutuelle', 'numero_securite_sociale',
            ])
        ));

        // Créer un dossier médical vide
        DossierMedical::create(['patient_id' => $patient->id]);

        return response()->json([
            'success' => true,
            'data'    => $patient->load('user'),
            'message' => 'Patient créé avec succès.',
        ], 201);
    }

    /**
     * GET /api/secretaire/patients/{id}
     */
    public function show(int $id): JsonResponse
    {
        $patient = Patient::with(['user', 'dossierMedical', 'rendezVous' => fn($q) => $q->orderByDesc('date_heure')->limit(5)])
            ->find($id);

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $patient]);
    }

    /**
     * PUT /api/secretaire/patients/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $patient = Patient::with('user')->find($id);

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $request->validate([
            'name'           => 'sometimes|string|max:255',
            'email'          => 'sometimes|email|unique:users,email,' . $patient->user_id,
            'telephone'      => 'nullable|string|max:20',
            'date_naissance' => 'nullable|date',
            'sexe'           => 'nullable|in:M,F,autre',
            'adresse'        => 'nullable|string',
            'ville'          => 'nullable|string|max:100',
            'code_postal'    => 'nullable|string|max:10',
            'mutuelle'       => 'nullable|string|max:100',
        ]);

        $patient->user->update($request->only(['name', 'email']));
        $patient->update($request->only([
            'telephone', 'date_naissance', 'sexe', 'adresse',
            'ville', 'code_postal', 'mutuelle', 'numero_securite_sociale',
            'contact_urgence_nom', 'contact_urgence_tel',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $patient->fresh()->load('user'),
            'message' => 'Patient mis à jour avec succès.',
        ]);
    }

    /**
     * DELETE /api/secretaire/patients/{id}
     * Archive le patient (soft-delete métier)
     */
    public function destroy(int $id): JsonResponse
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $patient->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Patient archivé avec succès.',
        ]);
    }

    /**
     * POST /api/secretaire/patients/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $patient->update(['is_archived' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Patient réactivé avec succès.',
        ]);
    }
}