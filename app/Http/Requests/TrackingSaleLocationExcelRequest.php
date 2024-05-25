<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TrackingSaleLocationExcelRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('report-excel-tracking-sale-locations');
    }


    public function rules(): array
    {
        return [
            'user_id' => 'nullable',
            'model_id' => 'nullable',
            'type' => 'in:enter,exit',
            'from' => 'date|nullable',
            'to' => 'date|nullable',
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
