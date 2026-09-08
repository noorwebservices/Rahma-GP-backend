<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateVoyageurProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_piece' => ['required', Rule::in(['cni', 'passport'])],
            'numero_piece' => ['nullable', 'string', 'max:255'],
            'cni_recto' => ['nullable', 'sometimes'],
            'cni_verso' => ['nullable', 'sometimes'],
            'mode_client' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
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
