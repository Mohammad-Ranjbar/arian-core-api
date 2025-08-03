<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarModelColorCodeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('car-model-color-codes.update');
    }

    public function rules(): array
    {
        return [
            'car_model_id' => 'required|exists:car_models,id',
            'variant' => Rule::unique('car_model_color_codes', 'variant')->where(function ($query) {
                $query->where('color_code',request('color_code'));
            })->ignore($this->route('car_model_color_code')),
            'color_code' => 'required|string'
        ];
    }
}
