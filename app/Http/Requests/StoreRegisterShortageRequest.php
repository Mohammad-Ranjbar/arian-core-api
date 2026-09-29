<?php

namespace App\Http\Requests;

use App\Models\RegisterShortage;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisterShortageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $vehicle = Vehicle::query()->where('vin',$this->input('vin'))->firstOrFail();
        return [
            'vin' => ['required', 'string', 'max:255', 'exists:vehicles,vin'],
            'item_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('register_shortages', 'item_no')
                    ->where(fn($query) => $query->where('vin', $this->input('vin'))),
            ],
            'item_description' => ['nullable', 'string'],
            'type' => [
                'required',
                Rule::in([RegisterShortage::TYPE_MAIN, RegisterShortage::TYPE_SINGLE]),
            ],
            'qty' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'routing_link_code' => [
                'nullable',
                'required_if:type,' . RegisterShortage::TYPE_MAIN,
                'string',
                'max:255',
            ],
            'main_item_shortage_id' => [
                'required_if:type,' . RegisterShortage::TYPE_MAIN,
                Rule::exists('main_item_shortages', 'id')
                    ->where('is_active', 1)
                    ->where('car_model_id',$vehicle->getAttribute('car_model_id'))
            ],
        ];
    }
}
