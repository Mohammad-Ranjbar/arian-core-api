<?php

namespace App\Http\Requests;

use App\Models\ErpProductItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssignItemToAlternativeCategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('alternative-category.assign-item');
    }


    public function rules(): array
    {

        return [

            'erp_product_item_id' => [
                'required',
                'integer',
                'exists:erp_product_items,id',
            ],

            'item_no' => [
                'required',
                'string',
                'max:255',
            ],

            'packing_qty' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'erp_product_item_id.required' => 'انتخاب محصول ERP الزامی است.',
            'erp_product_item_id.integer' => 'شناسه محصول ERP باید یک عدد صحیح باشد.',
            'erp_product_item_id.exists' => 'محصول ERP انتخاب‌شده معتبر نیست.',

            'packing_qty.required' => 'تعداد بسته‌بندی الزامی است.',
            'packing_qty.integer' => 'تعداد بسته‌بندی باید یک عدد صحیح باشد.',
            'packing_qty.min' => 'تعداد بسته‌بندی نمی‌تواند کمتر از صفر باشد.',
        ];
    }


    protected function prepareForValidation(): void
    {
        $itemNo = ErpProductItem::query()->find($this->input('erp_product_item_id'));
        if (!$itemNo) {
            throw new NotFoundHttpException('Item Not Found');
        }
        $itemNo = $itemNo->getAttribute('reference');
        $this->merge([
            'item_no' => $itemNo,
            'user_id' => Auth::id()
        ]);
    }
}
