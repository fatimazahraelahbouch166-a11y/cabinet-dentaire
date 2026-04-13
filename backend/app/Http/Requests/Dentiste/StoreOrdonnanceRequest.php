<?php

namespace App\Http\Requests\Dentiste;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdonnanceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'       => 'required|exists:patients,id',
            'consultation_id'  => 'nullable|exists:consultations,id',
            'date_ordonnance'  => 'required|date',
            'contenu'          => 'required|string',
            'notes'            => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required'      => 'Le patient est obligatoire.',
            'date_ordonnance.required' => 'La date de l\'ordonnance est obligatoire.',
            'contenu.required'         => 'Le contenu de l\'ordonnance est obligatoire.',
        ];
    }
}