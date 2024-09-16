<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
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
                    'values' => $vehicle->tracking()->get()->map(function ($record) {
                        return [
                            jdate($record->created_at)->toDateTimeString(),
                            $record->model?->title,
                            $record->user->name,
                            $record->type
                        ];
                    })->all()
                ],
                [
                    'title' => 'a',
                    'values' => Barcode::query()->where('vin',$vehicle->getAttribute('vin'))->get()->map(function ($record) {
                        return [
                            jdate($record->created_at)->toDateTimeString(),
                            $record->code,
                            $record->user->name,
                            $record->erpProductItem?->title,
                        ];
                    })->all()
                ]
            ]
        ]);
    }
}
