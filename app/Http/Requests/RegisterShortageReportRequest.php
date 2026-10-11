<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterShortageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vin' => ['nullable', 'string', 'max:255', 'exists:vehicles,vin'],
            'item_no' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $filterModes = collect([
                    filled($this->input('vin')),
                    filled($this->input('item_no')),
                    filled($this->input('from')) || filled($this->input('to')),
                ])->filter()->count();

                if ($filterModes === 0) {
                    $validator->errors()->add(
                        'filter',
                        'برای تهیه گزارش، شماره شاسی، بازه تاریخ یا کد قطعه را وارد کنید.'
                    );
                }

                if ($filterModes > 1) {
                    $validator->errors()->add(
                        'filter',
                        'در هر گزارش فقط یکی از فیلترهای شماره شاسی، بازه تاریخ یا کد قطعه قابل استفاده است.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'vin.string' => 'شماره شاسی باید متن باشد.',
            'vin.max' => 'شماره شاسی نباید بیشتر از ۲۵۵ نویسه باشد.',
            'vin.exists' => 'خودرویی با این شماره شاسی یافت نشد.',
            'item_no.string' => 'کد قطعه باید متن باشد.',
            'item_no.max' => 'کد قطعه نباید بیشتر از ۲۵۵ نویسه باشد.',
            'from.required_with' => 'وارد کردن تاریخ شروع بازه الزامی است.',
            'to.required_with' => 'وارد کردن تاریخ پایان بازه الزامی است.',
            'from.date_format' => 'تاریخ شروع باید میلادی و با قالب YYYY-MM-DD وارد شود.',
            'to.date_format' => 'تاریخ پایان باید میلادی و با قالب YYYY-MM-DD وارد شود.',
            'to.after_or_equal' => 'تاریخ پایان باید مساوی یا بعد از تاریخ شروع باشد.',
        ];
    }
}
