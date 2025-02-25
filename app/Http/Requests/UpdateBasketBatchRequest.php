<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBasketBatchRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('basket-batch-update');
    }


    public function rules(): array
    {
        return [
            'codes' => ['required', 'array'],
            'batch' => [ 'required', 'string'],
        ];
    }
}
