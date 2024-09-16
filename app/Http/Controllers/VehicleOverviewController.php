<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
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
                    'title' => 'بارکد قطعات',
                    'values' => Barcode::query()->where('vin',$vehicle->getAttribute('vin'))->get()->map(function ($record) {
                        return [
                            jdate($record->created_at)->toDateTimeString(),
                            $record->code,
                            $record->user->name,
                            $record->erpProductItem?->title,
                        ];
                    })->all()
                ],
                [
                    'title' => 'کنترل کیفیت',
                    'values' => ActionLog::query()
                        ->where('model_id',$vehicle->getAttribute('vin'))
                        ->where('action','update_qc_status')
                        ->get()->map(function ($record) {
                        return [
                            jdate($record->created_at)->toDateTimeString(),
                            $record->user->name,
                            (string)$record->data,
                        ];
                    })->all()
                ]
            ]
        ]);
    }
}
