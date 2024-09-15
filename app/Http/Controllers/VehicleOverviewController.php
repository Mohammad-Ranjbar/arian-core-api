<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleOverviewController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Vehicle $vehicle)
    {
        return response()->json([
            'items' => array_map(function ($no) {
                return [
                    'title'  => 'row '.$no,
                    'type'   => 'table',
                    'values' => array_map(function ($index) {
                        return [
                            'key'   => 'key '.$index,
                            'value' => 'value '.$index
                        ];
                    }, range(1, 10))
                ];
            } , range(1,5))
        ]);
    }
}
