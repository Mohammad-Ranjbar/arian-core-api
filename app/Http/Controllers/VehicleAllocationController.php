<?php

namespace App\Http\Controllers;

use App\Filters\VehicleAllocationFilter;
use App\Http\Resources\VehicleAllocationResource;
use App\Models\VehicleAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Pipeline;

class VehicleAllocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('vehicle-allocations');
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'id' => ['nullable', 'integer', 'min:1'],
            'vin' => ['nullable', 'string', 'max:255'],
            'production_order_number' => ['nullable', 'string', 'max:255'],
            'variant_code' => ['nullable', 'string', 'max:255'],
            'make_code_id' => ['nullable', 'integer', 'min:1'],
            'car_model_id' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'is_complete' => ['nullable', 'boolean'],
            'engine_no' => ['nullable', 'string', 'max:255'],
            'cabin_no' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date_from' => ['nullable', 'date_format:Y-m-d'],
            'due_date_to' => [
                'nullable', 'date_format:Y-m-d',
                ...($request->filled('due_date_from') ? ['after_or_equal:due_date_from'] : []),
            ],
            'created_at_from' => ['nullable', 'date_format:Y-m-d'],
            'created_at_to' => [
                'nullable', 'date_format:Y-m-d',
                ...($request->filled('created_at_from') ? ['after_or_equal:created_at_from'] : []),
            ],
        ]);

        $allocations = Pipeline::send(VehicleAllocation::query()
            ->whereIn('make_code_id', $request->user()->makeCodes()->select('make_codes.id')))
            ->through([VehicleAllocationFilter::class])
            ->thenReturn()
            ->with([
                'makeCode:id,title,reference',
                'carModel' => fn ($query) => $query->withoutEagerLoads()->select('id', 'title', 'reference'),
                'user:id,name',
            ])
            ->latest('id')
            ->paginate($validated['per_page'] ?? 10)
            ->withQueryString();

        return VehicleAllocationResource::collection($allocations);
    }
}
