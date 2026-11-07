<?php

namespace App\Http\Controllers;

use App\Services\Erp\VehicleAllocationService;
use Illuminate\Http\JsonResponse;

class VehicleAllocationController extends Controller
{
    public function store(VehicleAllocationService $service): JsonResponse
    {
        return response()->json([
            'message' => 'اطلاعات تخصیص خودرو با موفقیت دریافت و ذخیره شد.',
            'data' => $service->sync(),
        ]);
    }
}
