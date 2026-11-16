<?php

namespace App\Http\Requests;

use App\Models\IncidentSourceSelection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IncidentSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $rules = [
            'sources' => ['present', 'array', 'max:100'],
            'sources.*' => ['required', 'array:model_type,model_id'],
            'sources.*.model_type' => ['bail', 'required', 'string', Rule::in(array_keys(IncidentSourceSelection::MODEL_TYPES))],
            'sources.*.model_id' => ['bail', 'required', 'integer', 'min:1'],
        ];

        foreach ((array) $this->input('sources', []) as $index => $source) {
            $type = is_array($source) ? ($source['model_type'] ?? null) : null;
            if (is_string($type) && isset(IncidentSourceSelection::MODEL_TYPES[$type])) {
                $model = new (IncidentSourceSelection::MODEL_TYPES[$type]);
                $exists = Rule::exists($model->getTable(), 'id');
                if (method_exists($model, 'getDeletedAtColumn')) {
                    $exists->whereNull($model->getDeletedAtColumn());
                }
                $rules["sources.$index.model_id"] = ['bail', 'required', 'integer', 'min:1', $exists];
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $seen = [];
            $modelType = null;
            $sources = $this->input('sources');
            if (! is_array($sources)) {
                return;
            }
            foreach ($sources as $index => $source) {
                if ($validator->errors()->has("sources.$index.model_type") || $validator->errors()->has("sources.$index.model_id")) {
                    continue;
                }
                $modelType ??= $source['model_type'];
                if ($source['model_type'] !== $modelType) {
                    $validator->errors()->add("sources.$index.model_type", 'تمام منشأهای ایراد انتخاب‌شده باید از یک نوع باشند.');
                }
                $key = $source['model_type'].':'.(int) $source['model_id'];
                if (isset($seen[$key])) {
                    $validator->errors()->add("sources.$index.model_id", 'این منشأ ایراد در فهرست انتخاب‌ها تکراری است.');
                }
                $seen[$key] = true;
            }
        });
    }

    public function messages(): array
    {
        return [
            'sources.present' => 'ارسال فهرست منشأهای ایراد الزامی است.',
            'sources.array' => 'فهرست منشأهای ایراد باید آرایه باشد.',
            'sources.max' => 'حداکثر ۱۰۰ منشأ ایراد را می‌توانید انتخاب کنید.',
            'sources.*.required' => 'مشخصات هر منشأ ایراد الزامی است.',
            'sources.*.array' => 'هر منشأ ایراد باید فقط شامل نوع و شناسهٔ منشأ باشد.',
            'sources.*.model_type.required' => 'انتخاب نوع منشأ ایراد الزامی است.',
            'sources.*.model_type.string' => 'نوع منشأ ایراد باید رشتهٔ متنی باشد.',
            'sources.*.model_type.in' => 'نوع منشأ ایراد باید مرکز گروه کاری یا موقعیت باشد.',
            'sources.*.model_id.required' => 'انتخاب شناسهٔ منشأ ایراد الزامی است.',
            'sources.*.model_id.integer' => 'شناسهٔ منشأ ایراد باید عدد صحیح باشد.',
            'sources.*.model_id.min' => 'شناسهٔ منشأ ایراد باید بزرگ‌تر از صفر باشد.',
            'sources.*.model_id.exists' => 'منشأ ایراد انتخاب‌شده برای این نوع وجود ندارد یا حذف شده است.',
        ];
    }
}
