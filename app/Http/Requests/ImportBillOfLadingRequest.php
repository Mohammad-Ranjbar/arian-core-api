<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBillOfLadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('import.bill-of-lading');
    }

    public function rules(): array
    {
        return [
            'file'                    => 'required|exists:files,uuid',
            'location_source_id'      => 'required|exists:locations,id',
            'location_destination_id' => 'required|exists:locations,id',
            'type'                    => 'required|in:vehicles,bodies,containers',
            'item_type' => 'required_if:type,vehicles,bodies|in:0,1,2',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.exists'   => 'فایل انتخاب‌شده وجود ندارد.',

            'location_source_id.required' => 'انتخاب محل مبدأ الزامی است.',
            'location_source_id.exists'   => 'محل مبدأ انتخاب‌شده وجود ندارد.',

            'location_destination_id.required' => 'انتخاب محل مقصد الزامی است.',
            'location_destination_id.exists'   => 'محل مقصد انتخاب‌شده وجود ندارد.',

            'type.required' => 'انتخاب نوع الزامی است.',
            'type.in'       => 'نوع انتخاب‌شده باید یکی از موارد خودرو، بدنه یا کانتینر باشد.',

            'item_type.required_if' => 'در صورت انتخاب خودرو یا بدنه، انتخاب نوع آیتم بدنه خام بدنه رنگ شده خودرو الزامی است.',
            'item_type.in'          => 'نوع آیتم باید یکی از مقادیر بدنه خام بدنه رنگ شده خودرو باشد.',
        ];
    }
}
