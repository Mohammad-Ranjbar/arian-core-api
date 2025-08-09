<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreEnterEngineRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('enter-engines.store');
    }

    public function rules(): array
    {
        return [
            'engine_no' => 'required|string|unique:enter_engines,engine_no',
            'production_line_id' => 'required|exists:production_lines,id',
            'user_id' => 'nullable'
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id()
        ]);
    }
}
