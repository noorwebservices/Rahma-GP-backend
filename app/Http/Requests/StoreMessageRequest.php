<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'max:2000'],
            'piece_jointe' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Le contenu du message est obligatoire.',
            'contenu.string' => 'Le contenu du message doit être une chaîne de caractères.',
            'contenu.max' => 'Le message ne peut pas dépasser 2000 caractères.',
            'piece_jointe.string' => 'La pièce jointe doit être une chaîne de caractères.',
            'piece_jointe.max' => 'Le lien de la pièce jointe ne peut pas dépasser 500 caractères.',
        ];
    }
}
