<?php

namespace App\Http\Requests;

use App\Models\ErpProductItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AssignItemToAlternativeCategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('alternative-category.assign-item');
    }


    public function rules(): array
    {
        return [
            'erp_product_item_id' => 'required|exists:erp_product_items,id',
            'packing_qty' => 'required|numeric|min:0',
            'item_no' => 'nullable',
            'user_id' => 'nullable',
        ];
    }

    protected function prepareForValidation(): void
    {
        $itemNo = ErpProductItem::query()->find($this->input('erp_product_item_id'))->getAttribute('reference');
        $this->merge([
            'item_no' => $itemNo,
            'user_id' => Auth::id()
        ]);
    }
}
