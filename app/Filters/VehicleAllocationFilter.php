<?php

namespace App\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class VehicleAllocationFilter
{
    public function handle(Builder $query, Closure $next): Builder
    {
        $request = request();

        foreach (['id', 'production_order_number', 'variant_code', 'make_code_id', 'car_model_id', 'user_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('vin')) {
            $query->where('vin', 'like', '%'.$request->input('vin').'%');
        }

        // filled() also accepts 0, so incomplete allocations can be selected explicitly.
        if ($request->filled('is_complete')) {
            $query->where('is_complete', $request->boolean('is_complete'));
        }

        foreach (['engine_no', 'cabin_no'] as $field) {
            if ($request->filled($field)) {
                $query->whereHas('vehicle', fn (Builder $vehicle) => $vehicle->where($field, $request->input($field)));
            }
        }

        if ($request->filled('due_date')) {
            $query->whereDate('due_date', $request->input('due_date'));
        }

        foreach (['due_date', 'created_at'] as $field) {
            if ($request->filled($field.'_from')) {
                if ($field === 'due_date') {
                    $query->whereDate($field, '>=', $request->input($field.'_from'));
                } else {
                    $query->where($field, '>=', $request->input($field.'_from'));
                }
            }
            if ($request->filled($field.'_to')) {
                if ($field === 'created_at') {
                    // Include the entire final day without truncating fractional seconds.
                    $query->where($field, '<', Carbon::parse($request->input($field.'_to'))->addDay()->toDateString());
                } else {
                    $query->whereDate($field, '<=', $request->input($field.'_to'));
                }
            }
        }

        return $next($query);
    }
}
