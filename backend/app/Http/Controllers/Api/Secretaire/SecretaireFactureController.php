<?php

namespace App\Http\Controllers\Api\Secretaire;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\LigneFacture;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretaireFactureController extends Controller
{
    /**
     * GET /api/secretaire/factures
     */
    public function index(Request $request): JsonResponse
    {
        $query = Facture::with(['patient.user', 'lignes'])
            ->when($request->statut, fn($q) => $q->where('statut', $request->statut))
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id));

        $factures = $query->orderByDesc('date_facture')->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $factures]);
    }

    /**
     * POST /api/secretaire/factures
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'consultation_id'  => 'nullable|exists:consultations,id',
            'date_facture'     => 'required|date',
            'notes'            => 'nullable|string',
            'lignes'           => 'required|array|min:1',
            'lignes.*.libelle'       => 'required|string|max:255',
            'lignes.*.quantite'      => 'required|integer|min:1',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.catalogue_acte_id' => 'nullable|exists:catalogue_actes,id',
        ]);

        $montantTotal = collect($request->lignes)
            ->sum(fn($l) => $l['quantite'] * $l['prix_unitaire']);

        $facture = Facture::create([
            'patient_id'      => $request->patient_id,
            'secretaire_id'   => $request->user()->id,
            'consultation_id' => $request->consultation_id,
            'numero_facture'  => Facture::genererNumero(),
            'date_facture'    => $request->date_facture,
            'montant_total'   => $montantTotal,
            'montant_paye'    => 0,
            'montant_mutuelle'=> 0,
            'statut'          => 'en_attente',
            'notes'           => $request->notes,
        ]);

        foreach ($request->lignes as $ligne) {
            LigneFacture::create([
                'facture_id'        => $facture->id,
                'catalogue_acte_id' => $ligne['catalogue_acte_id'] ?? null,
                'libelle'           => $ligne['libelle'],
                'quantite'          => $ligne['quantite'],
                'prix_unitaire'     => $ligne['prix_unitaire'],
                'total'             => $ligne['quantite'] * $ligne['prix_unitaire'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $facture->load(['lignes', 'patient.user']),
            'message' => 'Facture créée avec succès.',
        ], 201);
    }

    /**
     * GET /api/secretaire/factures/{id}
     */
    public function show(int $id): JsonResponse
    {
        $facture = Facture::with(['patient.user', 'lignes.catalogueActe', 'paiements', 'secretaire:id,name'])
            ->find($id);

        if (!$facture) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $facture]);
    }

    /**
     * POST /api/secretaire/factures/{id}/paiements
     * Enregistrer un paiement
     */
    public function storePaiement(Request $request, int $id): JsonResponse
    {
        $facture = Facture::find($id);
        if (!$facture) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable.'], 404);
        }

        $request->validate([
            'montant'        => 'required|numeric|min:0.01',
            'mode_paiement'  => 'required|in:especes,carte,virement,mutuelle,cheque',
            'date_paiement'  => 'required|date',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string',
        ]);

        $paiement = Paiement::create([
            'facture_id'    => $facture->id,
            'montant'       => $request->montant,
            'mode_paiement' => $request->mode_paiement,
            'date_paiement' => $request->date_paiement,
            'reference'     => $request->reference,
            'notes'         => $request->notes,
        ]);

        // Mettre à jour montant_paye ou montant_mutuelle
        if ($request->mode_paiement === 'mutuelle') {
            $facture->increment('montant_mutuelle', $request->montant);
        } else {
            $facture->increment('montant_paye', $request->montant);
        }

        $facture->fresh()->recalculerStatut();

        return response()->json([
            'success' => true,
            'data'    => $paiement,
            'message' => 'Paiement enregistré.',
        ], 201);
    }

    /**
     * GET /api/secretaire/factures/impayes
     */
    public function impayes(): JsonResponse
    {
        $impayes = Facture::with(['patient.user'])
            ->whereIn('statut', ['en_attente', 'partiellement_paye'])
            ->orderBy('date_facture')
            ->get();

        return response()->json(['success' => true, 'data' => $impayes]);
    }
}