<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateColisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'valeur_estimee' => ['nullable', 'numeric', 'min:0'],
            'poids' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'est_fragile' => ['nullable', 'boolean'],
            'destinataire_nom' => ['sometimes', 'required', 'string', 'max:100'],
            'destinataire_prenom' => ['sometimes', 'required', 'string', 'max:100'],
            'destinataire_numero' => ['sometimes', 'required', 'string', 'max:50'],
            'destinataire_adresse' => ['sometimes', 'required', 'string', 'max:500'],
            'photo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de colis est obligatoire.',
            'type.string' => 'Le type de colis doit être une chaîne de caractères.',
            'type.max' => 'Le type de colis ne peut dépasser 100 caractères.',

            'description.string' => 'La description du colis doit être une chaîne de caractères.',
            'description.max' => 'La description du colis ne peut dépasser 1000 caractères.',

            'valeur_estimee.numeric' => 'La valeur estimée doit être un nombre valide.',
            'valeur_estimee.min' => 'La valeur estimée ne peut pas être négative.',

            'poids.required' => 'Le poids du colis (en kg) est obligatoire.',
            'poids.numeric' => 'Le poids du colis doit être un nombre valide.',
            'poids.gt' => 'Le poids du colis doit être strictement supérieur à 0.',

            'est_fragile.boolean' => 'L\'indication fragile doit être un booléen.',

            'destinataire_nom.required' => 'Le nom du destinataire est obligatoire.',
            'destinataire_nom.max' => 'Le nom du destinataire ne peut dépasser 100 caractères.',

            'destinataire_prenom.required' => 'Le prénom du destinataire est obligatoire.',
            'destinataire_prenom.max' => 'Le prénom du destinataire ne peut dépasser 100 caractères.',

            'destinataire_numero.required' => 'Le numéro de téléphone du destinataire est obligatoire.',
            'destinataire_numero.max' => 'Le numéro de téléphone du destinataire ne peut dépasser 50 caractères.',

            'destinataire_adresse.required' => 'L\'adresse du destinataire est obligatoire.',
            'destinataire_adresse.max' => 'L\'adresse du destinataire ne peut dépasser 500 caractères.',
        ];
    }
}
