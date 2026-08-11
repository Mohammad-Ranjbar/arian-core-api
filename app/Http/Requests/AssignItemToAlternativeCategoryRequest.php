<?php

namespace App\Http\Requests;

use App\Models\ErpProductItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssignItemToAlternativeCategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('alternative-category.assign-item');
    }


    public function rules(): array
    {
        $id = $this->route('alternative_category_item');

        return [
            'alternative_category_id' => [
                'required',
                'integer',
                'exists:alternative_categories,id',
            ],

            'erp_product_item_id' => [
                'required',
                'integer',
                'exists:erp_product_items,id',

                Rule::unique('alternative_category_items', 'erp_product_item_id')
                    ->where(function ($query) {
                        return $query->where(
                            'alternative_category_id',
                            $this->input('alternative_category_id')
                        );
                    })
                    ->ignore($id),
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
