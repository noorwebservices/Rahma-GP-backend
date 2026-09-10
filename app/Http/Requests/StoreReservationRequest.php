<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voyage_id' => ['required', 'uuid', 'exists:voyages,id'],
            'mode_paiement_souhaite' => ['required', 'string', 'in:wave,espece_depot,livraison'],

            'colis' => ['required', 'array'],
            'colis.type' => ['required', 'string', 'max:100'],
            'colis.description' => ['nullable', 'string', 'max:1000'],
            'colis.valeur_estimee' => ['nullable', 'numeric', 'min:0'],
            'colis.poids' => ['required', 'numeric', 'gt:0'],
            'colis.est_fragile' => ['nullable', 'boolean'],
            'colis.destinataire_nom' => ['required', 'string', 'max:100'],
            'colis.destinataire_prenom' => ['required', 'string', 'max:100'],
            'colis.destinataire_numero' => ['required', 'string', 'max:50'],
            'colis.destinataire_adresse' => ['required', 'string', 'max:500'],
            'colis.photo' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'voyage_id.required' => 'Le voyage sélectionné est obligatoire.',
            'voyage_id.uuid' => 'L\'identifiant du voyage doit être un UUID valide.',
            'voyage_id.exists' => 'Le voyage sélectionné est inexistant.',

            'mode_paiement_souhaite.required' => 'Le mode de paiement souhaité est obligatoire.',
            'mode_paiement_souhaite.in' => 'Le mode de paiement doit être : wave, espece_depot ou livraison.',

            'colis.required' => 'Les informations du colis sont obligatoires.',
            'colis.array' => 'Les informations du colis doivent être fournies sous forme de tableau.',

            'colis.type.required' => 'Le type de colis est obligatoire.',
            'colis.type.string' => 'Le type de colis doit être une chaîne de caractères.',
            'colis.type.max' => 'Le type de colis ne peut dépasser 100 caractères.',

            'colis.description.string' => 'La description du colis doit être une chaîne de caractères.',
            'colis.description.max' => 'La description du colis ne peut dépasser 1000 caractères.',

            'colis.valeur_estimee.numeric' => 'La valeur estimée doit être un nombre valide.',
            'colis.valeur_estimee.min' => 'La valeur estimée ne peut pas être négative.',

            'colis.poids.required' => 'Le poids du colis (en kg) est obligatoire.',
            'colis.poids.numeric' => 'Le poids du colis doit être un nombre valide.',
            'colis.poids.gt' => 'Le poids du colis doit être strictement supérieur à 0.',

            'colis.est_fragile.boolean' => 'L\'indication fragile doit être un booléen.',

            'colis.destinataire_nom.required' => 'Le nom du destinataire est obligatoire.',
            'colis.destinataire_nom.max' => 'Le nom du destinataire ne peut dépasser 100 caractères.',

            'colis.destinataire_prenom.required' => 'Le prénom du destinataire est obligatoire.',
            'colis.destinataire_prenom.max' => 'Le prénom du destinataire ne peut dépasser 100 caractères.',

            'colis.destinataire_numero.required' => 'Le numéro de téléphone du destinataire est obligatoire.',
            'colis.destinataire_numero.max' => 'Le numéro de téléphone du destinataire ne peut dépasser 50 caractères.',

            'colis.destinataire_adresse.required' => 'L\'adresse du destinataire est obligatoire.',
            'colis.destinataire_adresse.max' => 'L\'adresse du destinataire ne peut dépasser 500 caractères.',
        ];
    }
}
