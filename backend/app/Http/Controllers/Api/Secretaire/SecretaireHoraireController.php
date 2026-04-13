<?php

namespace App\Http\Controllers\Api\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Conge;
use App\Models\Horaire;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretaireHoraireController extends Controller
{
    /**
     * GET /api/secretaire/horaires
     */
    public function index(): JsonResponse
    {
        $horaires = Horaire::with('dentiste:id,name')
            ->orderBy('jour_semaine')
            ->get();

        return response()->json(['success' => true, 'data' => $horaires]);
    }

    /**
     * POST /api/secretaire/horaires
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'dentiste_id'  => 'required|exists:users,id',
            'jour_semaine' => 'required|integer|between:1,7',
            'heure_debut'  => 'required|date_format:H:i',
            'heure_fin'    => 'required|date_format:H:i|after:heure_debut',
        ]);

        $horaire = Horaire::updateOrCreate(
            ['dentiste_id' => $request->dentiste_id, 'jour_semaine' => $request->jour_semaine],
            ['heure_debut' => $request->heure_debut, 'heure_fin' => $request->heure_fin, 'is_active' => true]
        );

        return response()->json(['success' => true, 'data' => $horaire, 'message' => 'Horaire enregistré.'], 201);
    }

    /**
     * DELETE /api/secretaire/horaires/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $horaire = Horaire::find($id);
        if (!$horaire) {
            return response()->json(['success' => false, 'message' => 'Horaire introuvable.'], 404);
        }

        $horaire->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Horaire désactivé.']);
    }

    // ── Congés ────────────────────────────────────────────────────────────────

    /**
     * GET /api/secretaire/conges
     */
    public function indexConges(Request $request): JsonResponse
    {
        $conges = Conge::with('dentiste:id,name')
            ->when($request->dentiste_id, fn($q) => $q->where('dentiste_id', $request->dentiste_id))
            ->orderBy('date_debut')
            ->get();

        return response()->json(['success' => true, 'data' => $conges]);
    }

    /**
     * POST /api/secretaire/conges
     */
    public function storeConge(Request $request): JsonResponse
    {
        $request->validate([
            'dentiste_id' => 'required|exists:users,id',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after_or_equal:date_debut',
            'motif'       => 'nullable|string|max:255',
            'type'        => 'required|in:conge,blocage,formation,autre',
        ]);

        $conge = Conge::create($request->only(['dentiste_id', 'date_debut', 'date_fin', 'motif', 'type']));

        return response()->json(['success' => true, 'data' => $conge, 'message' => 'Congé créé.'], 201);
    }

    /**
     * DELETE /api/secretaire/conges/{id}
     */
    public function destroyConge(int $id): JsonResponse
    {
        $conge = Conge::find($id);
        if (!$conge) {
            return response()->json(['success' => false, 'message' => 'Congé introuvable.'], 404);
        }

        $conge->delete();

        return response()->json(['success' => true, 'message' => 'Congé supprimé.']);
    }
}