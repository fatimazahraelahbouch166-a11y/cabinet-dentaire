<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Ordonnance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientOrdonnanceController extends Controller
{
    /**
     * GET /api/patient/ordonnances
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Profil patient introuvable.'], 404);
        }

        $ordonnances = Ordonnance::with(['dentiste:id,name'])
            ->where('patient_id', $patient->id)
            ->where('is_archived', false)
            ->orderByDesc('date_ordonnance')
            ->get();

        return response()->json(['success' => true, 'data' => $ordonnances]);
    }

    /**
     * GET /api/patient/ordonnances/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $patient     = $request->user()->patient;
        $ordonnance  = Ordonnance::with(['dentiste:id,name'])
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$ordonnance) {
            return response()->json(['success' => false, 'message' => 'Ordonnance introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $ordonnance]);
    }
}