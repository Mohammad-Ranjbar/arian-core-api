<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('vehicle.update-grade-status');
    }


    public function rules(): array
    {
        return [
          'grade' => 'required|string'
        ];
    }
}
