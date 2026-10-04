<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveShortageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('resolve-shortage');
    }

    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'exists:vehicles,vin'],
            'register_shortage_id' => ['required', 'exists:register_shortages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.required' => 'وارد کردن شماره شاسی (VIN) الزامی است.',
            'vin.string' => 'شماره شاسی (VIN) باید متن باشد.',
            'vin.exists' => 'خودرویی با این شماره شاسی یافت نشد.',
            'register_shortage_id.required' => 'انتخاب کسری ثبت‌شده الزامی است.',
            'register_shortage_id.exists' => 'کسری ثبت‌شده انتخاب‌شده یافت نشد.',
        ];
    }
}
