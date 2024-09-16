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
            'items' => [
                [
                    'title' => 'رهگیری محصول',
                    'values' => $vehicle->tracking()->get()->map(function ($tracking) {
                        return [
                            $tracking->created_at,
                            $tracking->model->title,
                            $tracking->user->name,
                            $tracking->type
                        ];
                    })->all()
                ]
            ]
        ]);
    }
}
