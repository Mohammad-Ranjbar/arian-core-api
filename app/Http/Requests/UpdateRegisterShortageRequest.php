<?php

namespace App\Http\Requests;

use App\Models\RegisterShortage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRegisterShortageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vin' => ['sometimes', 'required', 'string', 'max:255', 'exists:vehicles,vin'],
            'item_no' => ['sometimes', 'required', 'string', 'max:255'],
            'item_description' => ['sometimes', 'nullable', 'string'],
            'type' => [
                'sometimes',
                'required',
                Rule::in([RegisterShortage::TYPE_MAIN, RegisterShortage::TYPE_SINGLE]),
            ],
            'qty' => ['sometimes', 'required', 'integer', 'min:1', 'max:4294967295'],
            'work_center' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => [
                'sometimes',
                'required',
                Rule::in([RegisterShortage::STATUS_HAS_SHORTAGE, RegisterShortage::STATUS_RESOLVED]),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $registerShortage = $this->route('register_shortage');

                if (! $registerShortage instanceof RegisterShortage) {
                    return;
                }

                $type = $this->input('type', $registerShortage->type);
                $workCenter = $this->exists('work_center')
                    ? $this->input('work_center')
                    : $registerShortage->work_center;

                if ($type === RegisterShortage::TYPE_MAIN && blank($workCenter)) {
                    $validator->errors()->add('work_center', 'The work center field is required when type is main.');
                }

                $vin = $this->input('vin', $registerShortage->vin);
                $itemNo = $this->input('item_no', $registerShortage->item_no);

                $duplicateExists = RegisterShortage::query()
                    ->where('vin', $vin)
                    ->where('item_no', $itemNo)
                    ->whereKeyNot($registerShortage->getKey())
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add('item_no', 'The item number has already been registered for this VIN.');
                }
            },
        ];
    }
}
