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
        $isVoyageur = $this->input('profile_type') === 'voyageur' || $this->filled('type_piece');

        return [
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:50', 'unique:users,telephone'],
            'email' => [$isVoyageur ? 'required' : 'nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'mot_de_passe' => ['required', 'string', 'min:6'],
            'adresse' => ['nullable', 'string'],
            'avatar' => ['nullable', 'sometimes'],
            'profile_type' => ['nullable', 'string', 'in:client,voyageur'],
            'type_piece' => ['nullable', 'string', 'in:cni,passport,passeport'],
            'numero_piece' => ['nullable', 'string', 'max:255'],
            'cni_recto' => ['nullable', 'sometimes'],
            'cni_verso' => ['nullable', 'sometimes'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('avatar')) {
                $subValidator = Validator::make(
                    ['avatar' => $this->file('avatar')],
                    ['avatar' => 'file|image|mimes:jpeg,png,jpg,gif,webp|max:5120']
                );

                if ($subValidator->fails()) {
                    $validator->errors()->add('avatar', $subValidator->errors()->first('avatar'));
                }
            }

            foreach (['cni_recto', 'cni_verso'] as $field) {
                if ($this->hasFile($field)) {
                    $subValidator = Validator::make(
                        [$field => $this->file($field)],
                        [$field => 'file|mimes:jpeg,png,jpg,pdf,webp|max:5120']
                    );

                    if ($subValidator->fails()) {
                        $validator->errors()->add($field, $subValidator->errors()->first($field));
                    }
                }
            }
        });
    }
}
