<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateColisStatutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut' => [
                'required',
                'string',
                'in:demande_envoyee,reservation_acceptee,colis_depose,colis_pris_en_charge,en_transit,arrive,livre',
            ],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'statut.required' => 'Le nouveau statut du colis est obligatoire.',
            'statut.in' => 'Le statut sélectionné est invalide. Valeurs acceptées : demande_envoyee, reservation_acceptee, colis_depose, colis_pris_en_charge, en_transit, arrive, livre.',
            'commentaire.string' => 'Le commentaire doit être une chaîne de caractères.',
            'commentaire.max' => 'Le commentaire ne peut dépasser 500 caractères.',
        ];
    }
}
