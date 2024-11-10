<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPalletRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('add-pallet');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'pallet_id' => 'required|integer|exists:pallets,id',
            'location_id' => 'required|integer|exists:locations,id',
            'qty' => 'required|integer|min:1',
        ];
    }
}
