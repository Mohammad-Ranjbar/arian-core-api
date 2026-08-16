<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendAlternativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('send-alternatives');
    }

    public function rules(): array
    {
        return [
            'make_code_reference' => 'required|string|exists:make_codes,reference',
            'item_no' => 'required|string|exists:alternatives,item_no',
        ];
    }

}
