<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class IncidentSourceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $table = Str::plural($this->input('model_type'));
        return [
            'model_type' => 'required|in:salon,production_line,station',
            'model_id'   => 'required_if:model_type,!=,""|exists:'.$table.',id',
        ];
    }
}
