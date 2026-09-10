<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode_paiement_souhaite' => ['sometimes', 'required', 'string', 'in:wave,espece_depot,livraison'],
        ];
    }

    public function messages(): array
    {
        return [
            'mode_paiement_souhaite.required' => 'Le mode de paiement souhaité est obligatoire.',
            'mode_paiement_souhaite.in' => 'Le mode de paiement doit être : wave, espece_depot ou livraison.',
        ];
    }
}
