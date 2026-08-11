<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlternativeCategoryItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'alternative_category_id' => $this->resource->alternative_category_id,
            'erp_product_item_id' => $this->resource->erp_product_item_id,
            'erp_product_item' => ErpProductItemResource::make($this->resource->erpProductItem),
            'packing_qty' => $this->resource->packing_qty,
            'user_id' => $this->resource->user_id,
            'user' => UserShortResource::make($this->resource->user),
            'item_no' => $this->resource->item_no,
            'created_at' => $this->resource->created_at,
        ];
    }

    public function messages(): array
    {
        return [
            'alternative_category_id.required' => 'انتخاب دسته‌بندی جایگزین الزامی است.',
            'alternative_category_id.integer' => 'شناسه دسته‌بندی جایگزین باید یک عدد صحیح باشد.',
            'alternative_category_id.exists' => 'دسته‌بندی جایگزین انتخاب‌شده معتبر نیست.',

            'erp_product_item_id.required' => 'انتخاب محصول ERP الزامی است.',
            'erp_product_item_id.integer' => 'شناسه محصول ERP باید یک عدد صحیح باشد.',
            'erp_product_item_id.exists' => 'محصول ERP انتخاب‌شده معتبر نیست.',
            'erp_product_item_id.unique' => 'این محصول قبلاً برای دسته‌بندی جایگزین انتخاب‌شده ثبت شده است.',

            'item_no.required' => 'شماره آیتم الزامی است.',
            'item_no.string' => 'شماره آیتم باید به صورت متن وارد شود.',
            'item_no.max' => 'شماره آیتم نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد.',

            'packing_qty.required' => 'تعداد بسته‌بندی الزامی است.',
            'packing_qty.integer' => 'تعداد بسته‌بندی باید یک عدد صحیح باشد.',
            'packing_qty.min' => 'تعداد بسته‌بندی نمی‌تواند کمتر از صفر باشد.',
        ];
    }

}
