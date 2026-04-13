<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientFactureController extends Controller
{
    /**
     * GET /api/patient/factures
     */
    public function index(Request $request): JsonResponse
    {
        $patient = $request->user()->patient;

        if (!$patient) {
            return response()->json(['success' => false, 'message' => 'Profil patient introuvable.'], 404);
        }

        $factures = Facture::with(['lignes.catalogueActe', 'paiements'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('date_facture')
            ->get();

        return response()->json(['success' => true, 'data' => $factures]);
    }

    /**
     * GET /api/patient/factures/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $patient  = $request->user()->patient;
        $facture  = Facture::with(['lignes.catalogueActe', 'paiements', 'secretaire:id,name'])
            ->where('id', $id)
            ->where('patient_id', $patient->id)
            ->first();

        if (!$facture) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $facture]);
    }
}