<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reservation_id' => ['required', 'uuid', 'exists:reservations,id', 'unique:colis,reservation_id'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'valeur_estimee' => ['nullable', 'numeric', 'min:0'],
            'poids' => ['required', 'numeric', 'gt:0'],
            'est_fragile' => ['nullable', 'boolean'],
            'destinataire_nom' => ['required', 'string', 'max:100'],
            'destinataire_prenom' => ['required', 'string', 'max:100'],
            'destinataire_numero' => ['required', 'string', 'max:50'],
            'destinataire_adresse' => ['required', 'string', 'max:500'],
            'photo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reservation_id.required' => 'La réservation est obligatoire.',
            'reservation_id.uuid' => 'L\'identifiant de la réservation doit être un UUID valide.',
            'reservation_id.exists' => 'La réservation sélectionnée est inexistante.',
            'reservation_id.unique' => 'Un colis est déjà associé à cette réservation.',

            'type.required' => 'Le type de colis est obligatoire.',
            'type.string' => 'Le type de colis doit être une chaîne de caractères.',
            'type.max' => 'Le type de colis ne peut dépasser 100 caractères.',

            'description.string' => 'La description du colis doit être une chaîne de caractères.',
            'description.max' => 'La description du colis ne peut dépasser 1000 caractères.',

            'valeur_estimee.numeric' => 'La valeur estimée doit être un nombre valide.',
            'valeur_estimee.min' => 'La valeur estimée ne peut pas être négative.',

            'poids.required' => 'Le poids du colis (en kg) est obligatoire.',
            'poids.numeric' => 'Le poids du colis doit être un nombre valide.',
            'poids.gt' => 'Le poids du colis doit être strictly supérieur à 0.',

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
