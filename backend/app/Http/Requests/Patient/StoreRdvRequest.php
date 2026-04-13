<?php

namespace App\Http\Requests\Patient;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRdvRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'dentiste_id'    => 'required|exists:users,id',
            'date_heure'     => 'required|date|after:now',
            'motif'          => 'required|string|min:3|max:255',
            'duree_minutes'  => 'nullable|integer|min:15|max:240',
            'is_urgence'     => 'nullable|boolean',
            'notes'          => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'dentiste_id.required' => 'Veuillez sélectionner un dentiste.',
            'dentiste_id.exists'   => 'Le dentiste sélectionné n\'existe pas.',
            'date_heure.required'  => 'La date et l\'heure du RDV sont obligatoires.',
            'date_heure.after'     => 'Le rendez-vous doit être dans le futur.',
            'motif.required'       => 'Le motif du rendez-vous est obligatoire.',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Données du rendez-vous invalides.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}