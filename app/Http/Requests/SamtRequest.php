<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SamtRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'VinNO' => 'nullable|string',
            'ChasiNO' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'ChasiNO.string' => 'نوع داده شماره شاسی باید رشته (string) باشد',
            'ChasiNO.required' => 'شماره شاسی اجباری است',
        ];
    }
}
