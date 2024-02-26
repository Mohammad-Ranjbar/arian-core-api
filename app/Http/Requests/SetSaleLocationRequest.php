<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetSaleLocationRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('set-sale-location');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'location_id' => 'required|exists:locations,id'
        ];
    }
}
