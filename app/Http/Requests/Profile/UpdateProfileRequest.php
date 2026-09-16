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

    protected function prepareForValidation(): void
    {
        $inputs = [];

        if ($this->has('current_password') && !$this->has('mot_de_passe_actuel')) {
            $inputs['mot_de_passe_actuel'] = $this->input('current_password');
        }

        if ($this->has('password') && !$this->has('mot_de_passe')) {
            $inputs['mot_de_passe'] = $this->input('password');
        }

        if ($this->has('password_confirmation') && !$this->has('mot_de_passe_confirmation')) {
            $inputs['mot_de_passe_confirmation'] = $this->input('password_confirmation');
        }

        if (!empty($inputs)) {
            $this->merge($inputs);
        }
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
            'mot_de_passe_actuel' => ['required_with:mot_de_passe', 'nullable', 'string'],
            'mot_de_passe' => ['nullable', 'string', 'min:6', 'confirmed'],
            'mot_de_passe_confirmation' => ['required_with:mot_de_passe', 'nullable', 'string'],
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
