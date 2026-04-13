<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogueActe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogueActeController extends Controller
{
    /**
     * GET /api/catalogue-actes
     */
    public function index(Request $request): JsonResponse
    {
        $actes = CatalogueActe::actifs()
            ->when($request->categorie, fn($q) => $q->where('categorie', $request->categorie))
            ->when($request->search, fn($q) => $q->where('libelle', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->orderBy('categorie')
            ->orderBy('libelle')
            ->get();

        $categories = CatalogueActe::actifs()
            ->distinct()
            ->pluck('categorie')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'actes'      => $actes,
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * POST /api/catalogue-actes
     * Réservé dentiste / secrétaire
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'         => 'required|string|unique:catalogue_actes,code',
            'libelle'      => 'required|string|max:255',
            'categorie'    => 'nullable|string|max:100',
            'tarif'        => 'required|numeric|min:0',
            'remboursable' => 'nullable|boolean',
            'description'  => 'nullable|string',
        ]);

        $acte = CatalogueActe::create([
            'code'         => $request->code,
            'libelle'      => $request->libelle,
            'categorie'    => $request->categorie,
            'tarif'        => $request->tarif,
            'remboursable' => $request->remboursable ?? false,
            'description'  => $request->description,
            'is_active'    => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $acte,
            'message' => 'Acte créé dans le catalogue.',
        ], 201);
    }

    /**
     * GET /api/catalogue-actes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $acte = CatalogueActe::find($id);

        if (!$acte) {
            return response()->json(['success' => false, 'message' => 'Acte introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $acte]);
    }

    /**
     * PUT /api/catalogue-actes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $acte = CatalogueActe::find($id);

        if (!$acte) {
            return response()->json(['success' => false, 'message' => 'Acte introuvable.'], 404);
        }

        $request->validate([
            'libelle'      => 'sometimes|string|max:255',
            'categorie'    => 'nullable|string|max:100',
            'tarif'        => 'sometimes|numeric|min:0',
            'remboursable' => 'nullable|boolean',
            'description'  => 'nullable|string',
        ]);

        $acte->update($request->only([
            'libelle',
            'categorie',
            'tarif',
            'remboursable',
            'description',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $acte->fresh(),
            'message' => 'Acte mis à jour.',
        ]);
    }

    /**
     * DELETE /api/catalogue-actes/{id}
     * Désactivation soft (is_active = false)
     */
    public function destroy(int $id): JsonResponse
    {
        $acte = CatalogueActe::find($id);

        if (!$acte) {
            return response()->json(['success' => false, 'message' => 'Acte introuvable.'], 404);
        }

        $acte->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Acte désactivé du catalogue.',
        ]);
    }
}