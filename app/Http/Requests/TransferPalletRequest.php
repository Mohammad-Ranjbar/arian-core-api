<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferPalletRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('transfer-pallet');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'pallet_id' => 'required|integer|exists:pallets,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id',
            'qty' => 'required|integer|min:1',
        ];
    }
}
