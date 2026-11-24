<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlternativeReportExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super-admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:success,send,failed'],
            'item_no' => ['nullable', 'string', 'max:255'],
            'created_at_from' => ['nullable', 'date'],
            'created_at_to' => ['nullable', 'date'],
        ];
    }
}
