<?php

namespace App\Http\Controllers\Api\Dentiste;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DentisteOrdonnanceController extends Controller
{
    /**
     * GET /api/dentiste/ordonnances
     */
    public function index(Request $request): JsonResponse
    {
        $dentiste = $request->user();

        $ordonnances = Ordonnance::with(['patient.user'])
            ->where('dentiste_id', $dentiste->id)
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when(!$request->with_archived, fn($q) => $q->where('is_archived', false))
            ->orderByDesc('date_ordonnance')
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $ordonnances]);
    }

    /**
     * POST /api/dentiste/ordonnances
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'consultation_id' => 'nullable|exists:consultations,id',
            'date_ordonnance' => 'required|date',
            'contenu'         => 'required|string',
            'notes'           => 'nullable|string',
        ]);

        $patient = Patient::find($request->patient_id);
        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Patient introuvable.'], 404);
        }

        $ordonnance = Ordonnance::create([
            'patient_id'      => $request->patient_id,
            'dentiste_id'     => $request->user()->id,
            'consultation_id' => $request->consultation_id,
            'date_ordonnance' => $request->date_ordonnance,
            'contenu'         => $request->contenu,
            'notes'           => $request->notes,
            'is_archived'     => false,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $ordonnance->load(['patient.user', 'dentiste:id,name']),
            'message' => 'Ordonnance créée avec succès.',
        ], 201);
    }

    /**
     * GET /api/dentiste/ordonnances/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $ordonnance = Ordonnance::with(['patient.user', 'dentiste:id,name', 'consultation'])
            ->where('dentiste_id', $request->user()->id)
            ->find($id);

        if (!$ordonnance) {
            return response()->json(['success' => false, 'message' => 'Ordonnance introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $ordonnance]);
    }

    /**
     * PUT /api/dentiste/ordonnances/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $ordonnance = Ordonnance::where('dentiste_id', $request->user()->id)->find($id);

        if (!$ordonnance) {
            return response()->json(['success' => false, 'message' => 'Ordonnance introuvable.'], 404);
        }

        $request->validate([
            'contenu' => 'sometimes|string',
            'notes'   => 'nullable|string',
        ]);

        $ordonnance->update($request->only(['contenu', 'notes']));

        return response()->json([
            'success' => true,
            'data'    => $ordonnance->fresh(),
            'message' => 'Ordonnance mise à jour.',
        ]);
    }

    /**
     * PATCH /api/dentiste/ordonnances/{id}/archive
     */
    public function archive(Request $request, int $id): JsonResponse
    {
        $ordonnance = Ordonnance::where('dentiste_id', $request->user()->id)->find($id);

        if (!$ordonnance) {
            return response()->json(['success' => false, 'message' => 'Ordonnance introuvable.'], 404);
        }

        $ordonnance->update(['is_archived' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Ordonnance archivée.',
        ]);
    }
}