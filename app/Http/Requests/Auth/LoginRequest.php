<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'telephone' => ['nullable', 'string'],
            'mot_de_passe' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $identifier = $this->input('login') ?? $this->input('email') ?? $this->input('telephone');
            $password = $this->input('mot_de_passe') ?? $this->input('password');

            if (empty($identifier)) {
                $validator->errors()->add('login', 'Veuillez fournir un email ou un numéro de téléphone.');
            }

            if (empty($password)) {
                $validator->errors()->add('mot_de_passe', 'Le mot de passe est obligatoire.');
            }
        });
    }

    public function getCredentials(): array
    {
        $identifier = $this->input('login') ?? $this->input('email') ?? $this->input('telephone');
        $password = $this->input('mot_de_passe') ?? $this->input('password');

        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'telephone';

        return [
            $field => $identifier,
            'password' => $password,
        ];
    }
}
