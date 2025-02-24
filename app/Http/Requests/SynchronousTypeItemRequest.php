<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SynchronousTypeItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'car_model_id' => 'required|exists:car_models,id',
            'erp_product_item_id' => 'required|exists:erp_product_item,id',
            'cross_reference' => 'required|string'
        ];
    }
}
