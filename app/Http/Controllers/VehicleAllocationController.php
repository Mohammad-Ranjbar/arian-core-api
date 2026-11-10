<?php

namespace App\Http\Controllers;

use App\Http\Resources\VehicleAllocationResource;
use App\Models\VehicleAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VehicleAllocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $allocations = VehicleAllocation::query()
            ->whereIn('make_code_id', $request->user()->makeCodes()->select('make_codes.id'))
            ->with([
                'makeCode:id,title,reference',
                'carModel' => fn ($query) => $query->withoutEagerLoads()->select('id', 'title', 'reference'),
                'user:id,name',
            ])
            ->latest('id')
            ->paginate($validated['per_page'] ?? 10);

        return VehicleAllocationResource::collection($allocations);
    }
}
