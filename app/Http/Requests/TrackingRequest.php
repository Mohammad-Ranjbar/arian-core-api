<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackingRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'model_type' => 'required|in:salon,production_line,station',
            //            TODO improved validation by model type
            'model_id'   => 'required',
            'type'       => 'required|in:enter,exit'
        ];
    }
}
