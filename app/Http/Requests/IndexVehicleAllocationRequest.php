<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexVehicleAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vehicle-allocations');
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'id' => ['nullable', 'integer', 'min:1'],
            'vin' => ['nullable', 'string', 'max:255'],
            'production_order_number' => ['nullable', 'string', 'max:255'],
            'variant_code' => ['nullable', 'string', 'max:255'],
            'make_code_id' => ['nullable', 'integer', 'min:1'],
            'car_model_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'is_complete' => ['nullable', 'boolean'],
            'engine_no' => ['nullable', 'string', 'max:255'],
            'cabin_no' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date_from' => ['nullable', 'date_format:Y-m-d'],
            'due_date_to' => [
                'nullable', 'date_format:Y-m-d',
                ...($this->filled('due_date_from') ? ['after_or_equal:due_date_from'] : []),
            ],
            'created_at_from' => ['nullable', 'date_format:Y-m-d'],
            'created_at_to' => [
                'nullable', 'date_format:Y-m-d',
                ...($this->filled('created_at_from') ? ['after_or_equal:created_at_from'] : []),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'integer' => ':attribute باید عدد صحیح باشد.',
            'min' => ':attribute باید حداقل :min باشد.',
            'string' => ':attribute باید رشتهٔ متنی باشد.',
            'max' => ':attribute نباید بیش از :max کاراکتر باشد.',
            'per_page.max' => 'تعداد رکورد در هر صفحه نباید بیشتر از :max باشد.',
            'is_complete.boolean' => 'وضعیت تکمیل باید ۰ (تکمیل‌نشده) یا ۱ (تکمیل‌شده) باشد.',
            'date_format' => ':attribute باید یک تاریخ معتبر میلادی با قالب YYYY-MM-DD باشد.',
            'due_date_to.after_or_equal' => 'تاریخ پایان سررسید باید برابر یا بعد از تاریخ شروع سررسید باشد.',
            'created_at_to.after_or_equal' => 'تاریخ پایان ایجاد باید برابر یا بعد از تاریخ شروع ایجاد باشد.',
        ];
    }

    public function attributes(): array
    {
        return [
            'per_page' => 'تعداد رکورد در هر صفحه',
            'id' => 'شناسهٔ کسری‌بندی',
            'vin' => 'شمارهٔ شاسی',
            'production_order_number' => 'شمارهٔ سفارش تولید',
            'variant_code' => 'کد واریانت',
            'make_code_id' => 'شناسهٔ برند خودرو',
            'car_model_id' => 'شناسهٔ مدل خودرو',
            'user_id' => 'شناسهٔ کاربر',
            'is_complete' => 'وضعیت تکمیل',
            'engine_no' => 'شمارهٔ موتور',
            'cabin_no' => 'شمارهٔ کابین',
            'due_date' => 'تاریخ سررسید',
            'due_date_from' => 'تاریخ شروع سررسید',
            'due_date_to' => 'تاریخ پایان سررسید',
            'created_at_from' => 'تاریخ شروع ایجاد',
            'created_at_to' => 'تاریخ پایان ایجاد',
        ];
    }
}
