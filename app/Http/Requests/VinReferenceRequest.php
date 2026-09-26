<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VinReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vin' => 'required|exists:vehicles,vin',
        ];
    }
}
