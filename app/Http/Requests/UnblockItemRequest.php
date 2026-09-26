<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnblockItemRequest extends FormRequest
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
            'unblock_description' => 'required|string|max:400',
        ];
    }

    public function messages(): array
    {
        return [
            'unblock_description.required' => 'وارد کردن توضیحات رفع مسدودی الزامی است.',
            'unblock_description.string' => 'توضیحات رفع مسدودی باید به صورت متن باشد.',
            'unblock_description.max' => 'توضیحات رفع مسدودی نباید بیشتر از ۴۰۰ کاراکتر باشد.',
        ];
    }
}
