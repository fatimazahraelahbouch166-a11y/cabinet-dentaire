<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PatientProfilController extends Controller
{
    /**
     * GET /api/patient/profil
     */
    public function show(Request $request): JsonResponse
    {
        $user    = $request->user()->load('patient');
        $patient = $user->patient;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'role'      => $user->role,
                'patient'   => $patient,
            ],
        ]);
    }

    /**
     * PUT /api/patient/profil
     */
    public function update(Request $request): JsonResponse
    {
        $user    = $request->user();
        $patient = $user->patient;

        $request->validate([
            'name'                   => 'sometimes|string|max:255',
            'email'                  => 'sometimes|email|unique:users,email,' . $user->id,
            'telephone'              => 'nullable|string|max:20',
            'date_naissance'         => 'nullable|date',
            'sexe'                   => 'nullable|in:M,F,autre',
            'adresse'                => 'nullable|string',
            'ville'                  => 'nullable|string|max:100',
            'code_postal'            => 'nullable|string|max:10',
            'mutuelle'               => 'nullable|string|max:100',
            'numero_mutuelle'        => 'nullable|string|max:50',
            'contact_urgence_nom'    => 'nullable|string|max:100',
            'contact_urgence_tel'    => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'email']));

        if ($patient) {
            $patient->update($request->only([
                'telephone', 'date_naissance', 'sexe', 'adresse',
                'ville', 'code_postal', 'mutuelle', 'numero_mutuelle',
                'contact_urgence_nom', 'contact_urgence_tel',
            ]));
        }

        return response()->json([
            'success' => true,
            'data'    => $user->fresh()->load('patient'),
            'message' => 'Profil mis à jour avec succès.',
        ]);
    }

    /**
     * PUT /api/patient/profil/password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }
}