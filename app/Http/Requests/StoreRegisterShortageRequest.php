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
        $vehicle = Vehicle::query()->where('vin', $this->input('vin'))->first();

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
            'routing_link_code' => [
                'nullable',
                'required_if:type,'.RegisterShortage::TYPE_MAIN,
                'string',
                'max:255',
            ],
            'main_item_shortage_id' => [
                'required_if:type,'.RegisterShortage::TYPE_MAIN,
                Rule::exists('main_item_shortages', 'id')
                    ->where('is_active', 1)
                    ->where('car_model_id', $vehicle?->getAttribute('car_model_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vin.required' => 'وارد کردن شماره شاسی (VIN) الزامی است.',
            'vin.string' => 'شماره شاسی (VIN) باید متن باشد.',
            'vin.max' => 'شماره شاسی (VIN) نباید بیشتر از ۲۵۵ نویسه باشد.',
            'vin.exists' => 'خودرویی با این شماره شاسی یافت نشد.',

            'item_no.required' => 'وارد کردن شماره قطعه الزامی است.',
            'item_no.string' => 'شماره قطعه باید متن باشد.',
            'item_no.max' => 'شماره قطعه نباید بیشتر از ۲۵۵ نویسه باشد.',
            'item_no.unique' => 'این شماره قطعه قبلاً برای این خودرو ثبت شده است.',
            'item_description.string' => 'شرح قطعه باید متن باشد.',

            'type.required' => 'انتخاب نوع کسری الزامی است.',
            'type.in' => 'نوع کسری باید «اصلی» یا «تکی» باشد.',
            'qty.required' => 'وارد کردن تعداد کسری الزامی است.',
            'qty.integer' => 'تعداد کسری باید عدد صحیح باشد.',
            'qty.min' => 'تعداد کسری باید حداقل ۱ باشد.',
            'qty.max' => 'تعداد کسری نباید بیشتر از ۴٬۲۹۴٬۹۶۷٬۲۹۵ باشد.',

            'routing_link_code.required_if' => 'برای کسری اصلی، وارد کردن کد مسیر الزامی است.',
            'routing_link_code.string' => 'کد مسیر باید متن باشد.',
            'routing_link_code.max' => 'کد مسیر نباید بیشتر از ۲۵۵ نویسه باشد.',
            'main_item_shortage_id.required_if' => 'برای کسری اصلی، انتخاب آیتم اصلی الزامی است.',
            'main_item_shortage_id.exists' => 'آیتم اصلی انتخاب‌شده باید فعال و متعلق به مدل خودروی انتخاب‌شده باشد.',
        ];
    }
}
