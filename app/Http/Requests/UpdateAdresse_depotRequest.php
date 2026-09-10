<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdresse_depotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse' => ['sometimes', 'required', 'string', 'max:255'],
            'ville' => ['sometimes', 'required', 'string', 'max:100'],
            'pays' => ['sometimes', 'required', 'string', 'max:100'],
            'horaire_ouverture' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'adresse.required' => 'L\'adresse est obligatoire.',
            'adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'adresse.max' => 'L\'adresse ne doit pas dépasser 255 caractères.',
            'ville.required' => 'La ville est obligatoire.',
            'ville.string' => 'La ville doit être une chaîne de caractères.',
            'ville.max' => 'La ville ne doit pas dépasser 100 caractères.',
            'pays.required' => 'Le pays est obligatoire.',
            'pays.string' => 'Le pays doit être une chaîne de caractères.',
            'pays.max' => 'Le pays ne doit pas dépasser 100 caractères.',
            'horaire_ouverture.string' => 'L\'horaire d\'ouverture doit être une chaîne de caractères.',
            'instructions.string' => 'Les instructions doivent être une chaîne de caractères.',
            'latitude.numeric' => 'La latitude doit être un nombre valide.',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.numeric' => 'La longitude doit être un nombre valide.',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
        ];
    }
}
