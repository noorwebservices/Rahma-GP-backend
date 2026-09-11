<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemandeRetraitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.gt' => 'Le montant du retrait doit être strictement supérieur à 0.',
        ];
    }
}
