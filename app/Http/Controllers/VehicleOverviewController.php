<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Barcode;
use App\Models\BillOfLadingItem;
use App\Models\BlockItem;
use App\Models\DWVehicleServiceHistoryTree;
use App\Models\ErpProductItem;
use App\Models\Location;
use App\Models\ProductionLine;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use App\Services\Erp\ErpGetVehicleComponent;
use Exception;
use Illuminate\Http\JsonResponse;

class VehicleOverviewController extends Controller
{
    public function __construct(private readonly ErpGetVehicleComponent $component)
    {

    }

    public function __invoke(Vehicle $vehicle): JsonResponse
    {
        try {
            $police = [
                'title'  => 'اطلاعات شماره گذاری',
                'type'   => 'table',
                'heads'  => [],
                'values' => [
                    [
                        'تاریخ بررسی پلیس',
                        $vehicle->getAttribute('data')['Police_Inspection_Date'] ?
                            jdate($vehicle->getAttribute('data')['Police_Inspection_Date'])->toDateString()
                            : '-'
                        ,
                    ],
                    [
                        'تاریخ شماره گذاری',
                        $vehicle->getAttribute('data')['Platting_Date'] ?
                            jdate($vehicle->getAttribute('data')['Platting_Date'])->toDateString()
                            : '-',
                    ],
                ]
            ];
        } catch (Exception $exception) {
            $police = null;
        }
        $result = DWVehicleServiceHistoryTree::query()
            ->groupBy(['VIN', 'Document No_'])
            ->where('VIN', $vehicle->getAttribute('vin'))
            ->selectRaw('VIN as vin, [Document No_] as document_no ,count(*) as count, max(Date) as date')
            ->get();

        $services = [];
        foreach ($result as $row) {
            $list = DWVehicleServiceHistoryTree::query()
                ->where('VIN', $row->vin)
                ->where('Document No_', $row->document_no)
                ->get();

            $items = [];
            foreach ($list as $item) {
                if ((int) $item->Quantity) {
                    $items[] = [
                        $item->Description.' '.$item->{'Description 2'},
                        (int) $item->Quantity,
                    ];
                }
            }
            if (!empty($items)) {
                $services[] = [
                    'title'  => 'سابقه تعمیرگاهی: '.$row->document_no,
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => $items
                ];
            }

        }

        $components = collect($this->component->get($vehicle->getAttribute('production_order_number')))->filter(function (
            $item
        ) {
            return $item->Quantity > 0;
        });
        $components->each(function ($item) {
            $erpProductItem = ErpProductItem::query()->where('reference', $item->Item_No)->first();
            if (empty($erpProductItem)) {
                $erpProductItem = $this->component->getItem($item->Item_No);
            }
            $item->productItem = $erpProductItem;
        });

        return response()->json([
            'items' => array_values(array_filter(array_merge([
                [
                    'title'  => 'رهگیری محصول',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => array_values(array_filter($vehicle->tracking()->get()->map(function ($record) {
                        if (
                            ($record->model_type == ProductionLine::class and $record->model_id == 1) or
                            ($record->model_type == Location::class and $record->model_id == 1)
                        ) {
                            return false;
                        }

                        return [
                            $record->created_at ?
                                jdate($record->created_at)->toDateTimeString()
                                : '-'
                            ,
                            $record->model?->title,
                            $record->user->name,
                            $record->type
                        ];
                    })->all()))
                ],
                [
                    'title'  => 'بارکد قطعات',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => Barcode::query()->where('vin', $vehicle->getAttribute('vin'))->get()->map(function (
                        $record
                    ) {
                        return [
                            $record->created_at ?
                                jdate($record->created_at)->toDateTimeString()
                                : '-'
                            ,
                            $record->code,
                            $record->user->name,
                            $record->erpProductItem?->title,
                        ];
                    })->all()
                ],
                $police,
                [
                    'title'  => 'کنترل کیفیت',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => ActionLog::query()
                        ->where('model_id', $vehicle->getAttribute('vin'))
                        ->where('action', 'update_qc_status')
                        ->get()->map(function ($record) {
                            return [
                                $record->created_at ?
                                    jdate($record->created_at)->toDateTimeString()
                                    : '-'
                                ,
                                $record->user->name,
                                (string) $record->data,
                            ];
                        })->all()
                ],
                [
                    'title'  => 'ایرادات کیفی',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => VehicleIncident::query()
                        ->where('vin', $vehicle->getAttribute('vin'))
                        ->get()->map(function ($record) {
                            return [
                                jdate($record->checked_at)->toDateTimeString(),
                                $record->description,
                                $record->type,
                                $record->method,
                                $record->intensity,
                                $record->user->name,
                                $record->element->title.' ('.$record->element->code.')',
                                $record->incident->title.' ('.$record->element->code.')'
                            ];
                        })->all()
                ],
                [
                    'title'  => 'بلوکه',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => BlockItem::query()
                        ->where('vin', $vehicle->getAttribute('vin'))
                        ->get()->map(function ($record) {
                            return [
                                $record->created_at ?
                                    jdate($record->created_at)->toDateTimeString()
                                    : '-'
                                ,
                                $record->description,
                                $record->blockReason?->title,
                                $record->blockedBy->name,
                                ($record->unblockedBy) ? "رفع شده" : '--',
                                ($record->unblockedBy) ? jdate($record->unblocked_at)->toDateTimeString() : '--'
                            ];
                        })->all()
                ],
                [
                    'title'  => 'حمل و نقل',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => BillOfLadingItem::query()
                        ->where('model_id', $vehicle->getAttribute('vin'))
                        ->whereDoesntHave('billOfLading', function ($q) {
                            $q->where('location_source_id', 4);
                            $q->orWhere('location_destination_id', 4);
                        })
                        ->get()->map(function ($record) {
                            return [
                                jdate($record->billOfLading->created_at)->toDateTimeString(),
                                $record->billOfLading->locationSource->title,
                                $record->billOfLading->locationDestination->title
                            ];
                        })->all()
                ],
                [
                    'title'  => 'اقلام مصرفی (BOM)',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => array_values($components->map(function ($item) {
                        return [
                            $item->productItem?->title,
                            $item->productItem?->description,
                            $item->productItem?->reference,
                            $item->Quantity
                        ];
                    })->all())
                ]
            ], $services)))
        ]);
    }
}
