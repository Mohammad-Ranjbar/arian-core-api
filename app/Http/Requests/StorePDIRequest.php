<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePDIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'pdi_result' => 'nullable|string|max:255',
            'pdi_result_description' => 'nullable|string|max:250',
            'pdi_result_description_2' => 'nullable|string|max:100',
            'mileage' => ['bail', 'nullable', 'numeric', 'gt:0', 'regex:/\A.{1,255}\z/s'],
            'police_inspection_date' => 'nullable|string|max:255',
        ];

        if ($this->route('vehicle')?->carModel?->production == 'imported') {
            $rules['model_year'] = 'nullable|string|max:255';
            $rules['engine_no'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'string' => ':attribute باید به صورت متن وارد شود.',
            'max' => ':attribute نباید بیشتر از :max نویسه باشد.',
            'mileage.numeric' => 'کارکرد خودرو باید عدد باشد.',
            'mileage.gt' => 'کارکرد خودرو باید بزرگ‌تر از صفر باشد.',
            'mileage.regex' => 'کارکرد خودرو نباید بیشتر از ۲۵۵ نویسه باشد.',
        ];
    }

    public function attributes(): array
    {
        return [
            'pdi_result' => 'نتیجه PDI',
            'pdi_result_description' => 'توضیحات نتیجه PDI',
            'pdi_result_description_2' => 'توضیحات تکمیلی نتیجه PDI',
            'mileage' => 'کارکرد خودرو',
            'police_inspection_date' => 'تاریخ بازرسی پلیس',
            'model_year' => 'سال مدل',
            'engine_no' => 'شماره موتور',
        ];
    }
}
