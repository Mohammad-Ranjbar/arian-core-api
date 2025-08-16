<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Barcode;
use App\Models\BillOfLadingItem;
use App\Models\CopPlan;
use App\Models\DWVehicleServiceHistoryTree;
use App\Models\ErpProductItem;
use App\Models\ItemInputChecklist;
use App\Models\ItemTechnicalChecklist;
use App\Models\Location;
use App\Models\PdiCheckList;
use App\Models\ProductBom;
use App\Models\ProductionChecklist;
use App\Models\ProductionControl;
use App\Models\ProductionLine;
use App\Models\ProductionTestResult;
use App\Models\SaleLocation;
use App\Models\Vehicle;
use App\Models\VehicleIncident;
use App\Services\Erp\ErpGetVehicleComponent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VehicleOverviewController extends Controller
{
    public function __construct(private readonly ErpGetVehicleComponent $component)
    {

    }

    /**
     * @throws ValidationException
     */
    public function __invoke(Vehicle $vehicle): JsonResponse
    {

        $fileRecord = ItemTechnicalChecklist::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->first();
        $list1 = [];
        if ($fileRecord) {
            $list1 = [
                [
                    'file'  => route('dl', ['path' => $fileRecord->file->path]),
                    'title' => $fileRecord->getAttribute('title')
                ],
            ];
        }

        ////////////
        $fileRecord = ProductionChecklist::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->first();
        $list2 = [];


        if ($fileRecord) {
            $list2 = [
                [
                    'file'  => route('dl', ['path' => $fileRecord->file->path]),
                    'title' => $fileRecord->getAttribute('title')
                ],
            ];
        }

        $pdiCheckLists = PdiCheckList::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->get();
        $pdiCheckData = [];


        if ($pdiCheckLists->count()) {
            foreach ($pdiCheckLists as $fileRecord) {
                $pdiCheckData[] =
                    [
                        'file'  => route('dl', ['path' => $fileRecord->file->path]),
                        'title' => $fileRecord->getAttribute('title')
                    ];
            }
        }

        ///////////////////////
        $fileRecords = ProductionControl::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->get();
        $controlList = [];


        if ($fileRecords->count()) {
            foreach ($fileRecords as $fileRecord) {
                $controlList[] =
                    [
                        'file'  => route('dl', ['path' => $fileRecord->file->path]),
                        'title' => $fileRecord->getAttribute('title')
                    ];
            }
        }

        ///////////////
        $fileRecord = ProductBom::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->first();
        $list3 = [
            [
                'file'  => 'https://qr.arian-dev.ir/',
                'title' => 'دریافت اطلاعات و مشخصات فنی'
            ],
        ];
        if ($fileRecord) {
            $list3[] = [
                'file'  => route('dl', ['path' => $fileRecord->file->path]),
                'title' => $fileRecord->getAttribute('title')
            ];
        }


        ///////////
        $fileRecord = CopPlan::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->first();

        $list4 = [];
        if ($fileRecord) {
            $list4[] = [
                'file'  => route('dl', ['path' => $fileRecord->file->path]),
                'title' => $fileRecord->getAttribute('title')
            ];
        }

////////
        $fileRecord = ItemInputChecklist::query()->whereHas('carModels', function ($q) use ($vehicle) {
            $q->where('car_model_id', $vehicle->getAttribute('car_model_id'));
        })->latest()->first();
        $list5 = [];
        if ($fileRecord) {
            $list5[] = [
                'file'  => route('dl', ['path' => $fileRecord->file->path]),
                'title' => $fileRecord->getAttribute('title')
            ];
        }

        $incidents = VehicleIncident::query()
            ->where('type', '!=','pdi')
            ->where('vin', $vehicle->getAttribute('vin'));
        $pdiIncidents = VehicleIncident::query()
            ->where('type','pdi')
            ->where('vin', $vehicle->getAttribute('vin'));

        $qcCheckedAt = $incidents->max('checked_at');
        $qcChecked = '';
        if ($qcCheckedAt) {
            $qcChecked = '(تاریخ بررسی و رفع ایراد '.jdate($qcCheckedAt)->addDays(3)->toDateString().') ';
        }


        try {
            $police = [
                'title'  => 'اطلاعات شماره گذاری',
                'type'   => 'table',
                'heads'  => [],
                'values' => [
                    [
                        'تاریخ بررسی پلیس',
                        ($vehicle->getAttribute('data')['Police_Inspection_Date'] and
                            $vehicle->getAttribute('data')['Police_Inspection_Date'] != '0001-01-01T00:00:00Z') ?
                            jdate($vehicle->getAttribute('data')['Police_Inspection_Date'])->toDateString()
                            : '-'
                        ,
                    ],
                    [
                        'تاریخ شماره گذاری',
                        ($vehicle->getAttribute('data')['Platting_Date'] and
                            $vehicle->getAttribute('data')['Platting_Date'] != '0001-01-01T00:00:00Z')
                            ?
                            jdate($vehicle->getAttribute('data')['Platting_Date'])->toDateString()
                            : '-',
                    ],
                    [
                        'تاریخ چاپ سند',
                        ($vehicle->getAttribute('data')['Platting_Date'] and
                            $vehicle->getAttribute('data')['Platting_Date'] != '0001-01-01T00:00:00Z')
                            ?
                            jdate($vehicle->getAttribute('data')['Platting_Date'])->toDateString()
                            : '-',
                    ],
                ]
            ];
        } catch (Exception $exception) {
            $police = null;
            logger($vehicle->getAttribute('data'));
            logger()->error($exception);
        }
        $result = DWVehicleServiceHistoryTree::query()
            ->groupBy(['VIN', 'Document_No'])
            ->where('VIN', $vehicle->getAttribute('vin'))
            ->selectRaw('VIN as vin, [Document_No] as document_no ,count(*) as count, max(Date) as date')
            ->get();

        $services = [];
        foreach ($result as $row) {
            $list = DWVehicleServiceHistoryTree::query()
                ->where('VIN', $row->vin)
                ->where('Document_No', $row->document_no)
                ->get();

            $items = [];
            foreach ($list as $item) {
                if ((int) $item->Quantity) {
                    $items[] = [
                        $item->Description.' '.$item->{'Description2'},
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


        try {
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

            $componentItems = [
                'title'  => 'فهرست قطعات ورودی - (BOM)',
                'type'   => 'table',
                'heads'  => [],
                'dl' => route('dl.bom', ['po' => $vehicle->getAttribute('production_order_number')]),
                'values' => array_values($components->map(function ($item) {
                    return [
                        $item->productItem?->title,
                        $item->productItem?->description,
                        $item->productItem?->reference,
                        $item->Quantity
                    ];
                })->all())
            ];
        } catch (Exception $exception) {
            $componentItems = null;
            logger($exception);
        }


        return response()->json([
            'items' => array_values(array_filter(array_merge([
                $componentItems,
                [
                    'title'  => 'کنترل اقلام ورودی',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $list5
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
                            $record->generalProductItem?->title,
                        ];
                    })->all()
                ],
                [
                    'title'  => 'کنترل حین تولید',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $controlList
                ],
                [
                    'title'  => 'چک لیست های کنترل حین تولید و کنترل نهایی محصول',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $list2
                ],
                [
                    'title'  => 'ایرادات کیفی حین تولید و کنترل نهایی محصول '.$qcChecked,
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => $incidents->get()->map(function ($record) {
                        return [
                            jdate($record->checked_at)->toDateString(),
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
                    'title'  => 'آزمون های انتهای خط',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => ProductionTestResult::query()
                        ->whereHas('productionTest',function ($query) {
                            $query->where('type','others');
                        })
                        ->where('vin', $vehicle->vin)->get()->map(function ($record
                    ) {
                        return [
                            $record->productionTest->title,
                            $record->user->name,
                            $record->created_at ?
                                jdate($record->created_at)->toDateTimeString()
                                : '-',
                            $record->description
                        ];
                    })->all()
                ],
                [
                    'title'  => 'آزمون های PDI',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => ProductionTestResult::query()
                        ->whereHas('productionTest',function ($query) {
                            $query->where('type','pdi');
                        })
                        ->where('vin', $vehicle->vin)->get()->map(function ($record
                    ) {
                        return [
                            $record->productionTest->title,
                            $record->user->name,
                            $record->created_at ?
                                jdate($record->created_at)->toDateTimeString()
                                : '-',
                            $record->description
                        ];
                    })->all()
                ],
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
                    'title'  => 'اطلاعات PDI',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => [
                        [
                            $vehicle->getAttribute('data')['PDI_Result'],
                            $vehicle->getAttribute('data')['PDI_Result_Description'].' '.$vehicle->getAttribute('data')['PDI_Result_Description_2'],
                            (is_numeric(strpos($vehicle->getAttribute('data')['PDI_Result'],
                                    'Y')) or is_numeric(strpos($vehicle->getAttribute('data')['PDI_Result'], 'G'))) ?
                                ($r = $vehicle->tracking()->where('model_type',
                                    SaleLocation::class)->latest()->first()) ?
                                    'تاریخ بررسی و رفع ایراد: '.
                                    jdate($r->getAttribute('created_at'))->toDateString()
                                    : ''
                                : ''
                        ]
                    ]
                ],
                [
                    'title'  => 'چک لیست های PDI',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $pdiCheckData
                ],
                [
                    'title'  => 'ایرادات PDI',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => $pdiIncidents->get()->map(function ($record) {
                        return [
                            jdate($record->checked_at)->toDateString(),
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
                /*
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
  */
                [
                    'title'  => 'رهگیری محصول - تاریخ تولید',
                    'type'   => 'table',
                    'heads'  => [
                        'ثبت شده توسط',
                        'زمان',
                        'مکان',
                        'نوع',
                    ],
                    'values' => array_values(array_filter($vehicle->tracking()->get()->map(function ($record) {
                        if (
                            ($record->model_type == ProductionLine::class and $record->model_id == 1) or
                            ($record->model_type == Location::class and $record->model_id == 1)
                        ) {
                            return false;
                        }

                        return [
                            $record->user->name,
                            $record->created_at ?
                                jdate($record->created_at)->toDateTimeString()
                                : '-'
                            ,
                            $record->model?->title,
                            $record->type == 'enter' ? 'ورود' : 'خروج'
                        ];
                    })->all()))
                ],
                [
                    'title'  => 'حمل و نقل',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => array_values(array_filter(BillOfLadingItem::query()
                        ->where('model_id', $vehicle->getAttribute('vin'))
//                        ->whereDoesntHave('billOfLading', function ($q) {
//                            $q->where('location_source_id', 4);
//                            $q->orWhere('location_destination_id', 4);
//                        })
                        ->get()->map(function ($record) {
//                            if (
//                                $record->billOfLading->locationSource->id === 4 or
//                                $record->billOfLading->locationDestination->id === 4
//                            ) {
//                                return null;
//                            }
                            return [
                                jdate($record->billOfLading->created_at)->toDateTimeString(),
                                'شماره بارنامه: '.$record->billOfLading->code,
                                'مبدا: '.$record->billOfLading->locationSource->title,
                                'مقصد: '.$record->billOfLading->locationDestination->title
                            ];
                        })->all()
                    ))
                ],
                [
                    'title'  => 'آرشیو فنی محصولات',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $list4
                ],
                [
                    'title'  => 'شناسنامه فنی قطعات',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $list1
                ],
                $police,
                [
                    'title'  => 'ویژگی های فنی و عمومی محصول',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $list3
                ],
            ], $services)))
        ]);
    }
}
