<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
        $table = Str::plural($this->input('model_type'));
        return [
            'model_type' => 'required|in:salon,production_line,station',
            'model_id'   => 'required_if:model_type|exists:'.$table.',id',
            'type'       => 'required|in:enter,exit',
            'vin'        => 'nullable',
            'user_id'    => 'nullable'
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'vin'     => $this->route()->parameter('vehicle'),
            'user_id' => Auth::id()
        ]);
    }
}
