<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'qty' => 'required|integer|min:1',
        ];
    }
}
