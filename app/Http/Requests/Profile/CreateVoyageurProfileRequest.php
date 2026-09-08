<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
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
            'cni_recto' => ['nullable', 'string', 'max:255'],
            'cni_verso' => ['nullable', 'string', 'max:255'],
            'mode_client' => ['nullable', 'boolean'],
        ];
    }
}
