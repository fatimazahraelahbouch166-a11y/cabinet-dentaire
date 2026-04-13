<?php

namespace App\Http\Requests\Dentiste;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rendez_vous_id'            => 'nullable|exists:rendez_vous,id',
            'date_consultation'         => 'required|date',
            'motif'                     => 'required|string|max:255',
            'diagnostic'                => 'nullable|string',
            'notes'                     => 'nullable|string',
            'observations'              => 'nullable|string',
            'actes'                     => 'nullable|array',
            'actes.*.catalogue_acte_id' => 'required|exists:catalogue_actes,id',
            'actes.*.quantite'          => 'required|integer|min:1',
            'actes.*.prix_unitaire'     => 'required|numeric|min:0',
            'actes.*.dent'              => 'nullable|string|max:10',
            'actes.*.notes'             => 'nullable|string',
        ];
    }
}