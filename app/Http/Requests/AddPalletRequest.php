<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'qty' => 'required|integer',
            'user_id' => 'nullable'
        ];
    }

    public function prepareForValidation()
    {
        return $this->merge([
            'user_id' => Auth::id()
        ]);
    }
}
