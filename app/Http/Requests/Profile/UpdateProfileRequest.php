<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'telephone' => ['sometimes', 'string', 'max:50', Rule::unique('users', 'telephone')->ignore($userId)],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'adresse' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string'],
            'mot_de_passe' => ['nullable', 'string', 'min:6'],
        ];
    }
}
