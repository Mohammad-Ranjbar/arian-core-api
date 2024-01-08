<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TrackingRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('production-tracking.'.$this->input('model_type'));
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {

        return [
            'model_type' => 'bail|required|in:salons,production_lines,stations,inventories,sale_locations,locations',
            'model_id'   => 'required_if:model_type,!=,""|exists:'.$this->input('model_type').',id',
            'type'       => 'required|in:enter,exit',
            'vin'        => 'nullable',
            'user_id'    => 'nullable'
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'vin'     => $this->route()->parameter('vehicle')->vin,
            'user_id' => Auth::id()
        ]);
    }
}
