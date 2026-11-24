<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class AlternativeFilters
{
    public static function apply(Builder $query, array $filters): Builder
    {
        if ($status = $filters['status'] ?? null) {
            if ($status === 'success') {
                $query->whereNotNull('success_at')->whereHas('alternativeItem', fn ($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            } elseif ($status === 'send') {
                $query->whereNull('success_at')->whereHas('alternativeItem', fn ($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            } elseif ($status === 'failed') {
                $query->whereNotNull('failed_at')->whereNull('success_at')->whereHas('alternativeItem', fn ($q) => $q->whereColumn('packing_qty', 'alternatives.counter'));
            }
        }

        if ($itemNo = $filters['item_no'] ?? null) {
            $query->where('item_no', 'LIKE', '%'.$itemNo.'%');
        }
        if ($from = $filters['created_at_from'] ?? null) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $filters['created_at_to'] ?? null) {
            $query->where('created_at', '<=', $to);
        }

        return $query;
    }
}
