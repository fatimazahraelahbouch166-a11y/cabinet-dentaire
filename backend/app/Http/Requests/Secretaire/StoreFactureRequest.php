<?php

namespace App\Http\Requests\Secretaire;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactureRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'                     => 'required|exists:patients,id',
            'consultation_id'                => 'nullable|exists:consultations,id',
            'date_facture'                   => 'required|date',
            'notes'                          => 'nullable|string',
            'lignes'                         => 'required|array|min:1',
            'lignes.*.catalogue_acte_id'     => 'nullable|exists:catalogue_actes,id',
            'lignes.*.libelle'               => 'required|string|max:255',
            'lignes.*.quantite'              => 'required|integer|min:1',
            'lignes.*.prix_unitaire'         => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'        => 'Le patient est obligatoire.',
            'date_facture.required'      => 'La date de facturation est obligatoire.',
            'lignes.required'            => 'La facture doit contenir au moins un acte.',
            'lignes.*.libelle.required'  => 'Le libellé de chaque ligne est obligatoire.',
            'lignes.*.quantite.required' => 'La quantité de chaque ligne est obligatoire.',
            'lignes.*.prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
        ];
    }
}