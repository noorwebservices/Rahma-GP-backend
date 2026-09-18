<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoyageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_depot_id' => ['required', 'uuid', 'exists:adresse_depots,id'],
            'adresse_recuperation_id' => ['required', 'uuid', 'exists:adresse_recuperations,id'],
            'pays_depart' => ['required', 'string', 'max:100'],
            'ville_depart' => ['required', 'string', 'max:100'],
            'pays_destination' => ['required', 'string', 'max:100'],
            'ville_destination' => ['required', 'string', 'max:100'],
            'date_depart' => ['required', 'date', 'after_or_equal:today'],
            'date_arrivee' => ['required', 'date', 'after:date_depart'],
            'capacite_totale' => ['required', 'numeric', 'gt:0'],
            'prix_kg' => ['nullable', 'numeric', 'min:0'],
            'prix_objet' => ['nullable', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:1000'],
            'objets_autorises' => ['nullable', 'array'],
            'objets_autorises.*' => ['string'],
            'objets_interdits' => ['nullable', 'array'],
            'objets_interdits.*' => ['string'],
            'statut' => ['nullable', 'string', 'in:brouillon,publie,complet,en_cours,termine,annule,ferme'],
        ];
    }

    public function messages(): array
    {
        return [
            'adresse_depot_id.required' => 'L\'adresse de dépôt est obligatoire.',
            'adresse_depot_id.uuid' => 'L\'adresse de dépôt doit être un UUID valide.',
            'adresse_depot_id.exists' => 'L\'adresse de dépôt sélectionnée est inexistante.',
            'adresse_recuperation_id.required' => 'L\'adresse de récupération est obligatoire.',
            'adresse_recuperation_id.uuid' => 'L\'adresse de récupération doit être un UUID valide.',
            'adresse_recuperation_id.exists' => 'L\'adresse de récupération sélectionnée est inexistante.',
            'pays_depart.required' => 'Le pays de départ est obligatoire.',
            'pays_depart.string' => 'Le pays de départ doit être une chaîne de caractères.',
            'pays_depart.max' => 'Le pays de départ ne peut dépasser 100 caractères.',
            'ville_depart.required' => 'La ville de départ est obligatoire.',
            'ville_depart.string' => 'La ville de départ doit être une chaîne de caractères.',
            'ville_depart.max' => 'La ville de départ ne peut dépasser 100 caractères.',
            'pays_destination.required' => 'Le pays de destination est obligatoire.',
            'pays_destination.string' => 'Le pays de destination doit être une chaîne de caractères.',
            'pays_destination.max' => 'Le pays de destination ne peut dépasser 100 caractères.',
            'ville_destination.required' => 'La ville de destination est obligatoire.',
            'ville_destination.string' => 'La ville de destination doit être une chaîne de caractères.',
            'ville_destination.max' => 'La ville de destination ne peut dépasser 100 caractères.',
            'date_depart.required' => 'La date de départ est obligatoire.',
            'date_depart.date' => 'La date de départ doit être une date valide.',
            'date_depart.after_or_equal' => 'La date de départ doit être aujourd\'hui ou une date future.',
            'date_arrivee.required' => 'La date d\'arrivée est obligatoire.',
            'date_arrivee.date' => 'La date d\'arrivée doit être une date valide.',
            'date_arrivee.after' => 'La date d\'arrivée doit être strictement supérieure à la date de départ.',
            'capacite_totale.required' => 'La capacité totale (en kg) est obligatoire.',
            'capacite_totale.numeric' => 'La capacité totale doit être un nombre valide.',
            'capacite_totale.gt' => 'La capacité totale doit être strictement supérieure à 0.',
            'prix_kg.numeric' => 'Le prix par kilo doit être un nombre valide.',
            'prix_kg.min' => 'Le prix par kilo ne peut pas être négatif.',
            'prix_objet.numeric' => 'Le prix par objet doit être un nombre valide.',
            'prix_objet.min' => 'Le prix par objet ne peut pas être négatif.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.max' => 'La devise ne peut dépasser 10 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max' => 'La description ne peut dépasser 1000 caractères.',
            'objets_autorises.array' => 'Les objets autorisés doivent être sous forme de liste.',
            'objets_interdits.array' => 'Les objets interdits doivent être sous forme de liste.',
            'statut.in' => 'Le statut sélectionné est invalide. Valeurs acceptées: brouillon, publie, complet, en_cours, termine, annule, ferme.',
        ];
    }
}
