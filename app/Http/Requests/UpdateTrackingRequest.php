<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateTrackingRequest extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('update-production-tracking');
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {

        return [
            'model_type' => 'bail|required|in:salons,production_lines,stations,inventories',
            'model_id'   => 'required_if:model_type,!=,""|exists:'.$this->input('model_type').',id',
            'type'       => 'required|in:enter,exit',
            'user_id'    => 'nullable'
        ];
    }

    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id()
        ]);
    }
}
