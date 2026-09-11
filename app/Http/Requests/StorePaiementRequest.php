<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant' => ['nullable', 'numeric', 'gt:0'],
            'mode_paiement' => ['required', 'string', 'in:wave,espece_depot,livraison'],
            'statut' => ['nullable', 'string', 'in:en_attente,reussi,echoue'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.numeric' => 'Le montant doit être un nombre valide.',
            'montant.gt' => 'Le montant doit être strictement supérieur à 0.',
            'mode_paiement.required' => 'Le mode de paiement est obligatoire.',
            'mode_paiement.in' => 'Le mode de paiement doit être : wave, espece_depot ou livraison.',
            'statut.in' => 'Le statut sélectionné est invalide.',
        ];
    }
}
