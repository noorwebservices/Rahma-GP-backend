<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:50', 'unique:users,telephone'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mot_de_passe' => ['required', 'string', 'min:6'],
            'adresse' => ['nullable', 'string'],
            'avatar' => ['nullable', 'sometimes'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('avatar')) {
                $subValidator = Validator::make(
                    ['avatar' => $this->file('avatar')],
                    ['avatar' => 'file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120']
                );

                if ($subValidator->fails()) {
                    $validator->errors()->add('avatar', $subValidator->errors()->first('avatar'));
                }
            }
        });
    }
}
