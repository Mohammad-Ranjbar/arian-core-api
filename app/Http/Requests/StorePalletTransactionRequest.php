<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePalletTransactionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pallet_id' => 'required|exists:pallets,id',
            'location_id' => 'required|exists:locations,id',
            'qty' => 'required|integer',
            'user_id' => 'nullable',
            'description' => 'nullable',
        ];
    }

    public function prepareForValidation()
    {
        $type = $this->get('qty') < 0 ? 'decrease' : 'increase';
        return $this->merge([
            'type' => $type,
            'user_id' => Auth::id()
        ]);
    }
}
