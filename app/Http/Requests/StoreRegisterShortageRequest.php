<?php

namespace App\Http\Requests;

use App\Models\RegisterShortage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisterShortageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'vin' => ['required', 'string', 'max:255', 'exists:vehicles,vin'],
            'item_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('register_shortages', 'item_no')
                    ->where(fn ($query) => $query->where('vin', $this->input('vin'))),
            ],
            'item_description' => ['nullable', 'string'],
            'type' => [
                'required',
                Rule::in([RegisterShortage::TYPE_MAIN, RegisterShortage::TYPE_SINGLE]),
            ],
            'qty' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'work_center' => [
                'nullable',
                'required_if:type,'.RegisterShortage::TYPE_MAIN,
                'string',
                'max:255',
            ],
        ];
    }
}
