<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
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
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'adresse' => ['nullable', 'string'],
            'avatar' => ['nullable', 'sometimes'],
            'mot_de_passe' => ['nullable', 'string', 'min:6'],
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
