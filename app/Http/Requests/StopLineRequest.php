<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StopLineRequest extends FormRequest
{

    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('production-lines.stop');
    }

    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'station_id'     => 'required|exists:stations,id',
            'stop_reason_id' => 'required|exists:stop_reasons,id',
            'stop_type_id'   => 'required|exists:stop_types,id',
            'description'    => 'required',
            'note'           => 'nullable',
            'start_at'       => 'nullable|date_format:"Y-m-d H:i:s"',
            'stop_at'        => 'nullable|date_format:"Y-m-d H:i:s"',
        ];
    }
}
