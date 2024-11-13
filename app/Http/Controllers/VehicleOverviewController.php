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

        $qrLinks = [
            [
                'file'  => 'https://qr.arian-dev.ir/',
                'title' => 'دریافت اطلاعات و مشخصات فنی'
            ],
        ];

        $copList = [
            [
                'file'  => '/api/dl-file?path=cop/lamari/1.pdf',
                'title' => 'فایل جامع طرح انطباق تولید لاماری'
            ],
            [
                'file'  => '/api/dl-file?path=cop/jac.zip',
                'title' => 'فایل جامع طرح انطباق تولید جک'
            ],
            [
                'file'  => '/api/dl-file?path=cop/dumper.zip',
                'title' => 'فایل جامع طرح انطباق تولید دامپر'
            ],
            [
                'file'  => '/api/dl-file?path=cop/lorry.zip',
                'title' => 'فایل جامع طرح انطباق تولید لاری'
            ],
            [
                'file'  => '/api/dl-file?path=cop/X5000-COP-PLAN.pdf',
                'title' => 'فایل جامع طرح انطباق تولید کشنده'
            ],
        ];

        $incomingControlList = [
            [
                'file'  => '/api/dl-file?path=incoming-control/JAC-Incoming-Control.zip',
                'title' => 'جک'
            ],
            [
                'file'  => '/api/dl-file?path=incoming-control/Lamari-Incoming-Control.zip',
                'title' => 'لاماری'
            ],
            [
                'file'  => '/api/dl-file?path=incoming-control/Lorry-Dumper-Incoming-Control.zip',
                'title' => 'لاری و دامپر'
            ],
            [
                'file'  => '/api/dl-file?path=incoming-control/Shacman-Incoming-Control.zip',
                'title' => 'کشنده'
            ],
        ];

        $incidents = VehicleIncident::query()
            ->where('vin', $vehicle->getAttribute('vin'));

        $qcCheckedAt = $incidents->max('checked_at');
        $qcChecked = '';
        if ($qcCheckedAt) {
            $qcChecked = '(بررسی شده در تاریخ '.jdate($qcCheckedAt)->addDays(3)->toDateString().') ';
        }


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
                    [
                        'تاریخ چاپ سند',
                        $vehicle->getAttribute('data')['Platting_Date'] ?
                            jdate($vehicle->getAttribute('data')['Platting_Date'])->toDateString()
                            : '-',
                    ],
                ]
            ];
        } catch (Exception $exception) {
            $police = null;
            logger()->error($exception);
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
                    'title'  => 'فهرست قطعات ورودی - (BOM)',
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
                ],
                [
                    'title'  => 'کنترل اقلام ورودی',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $incomingControlList
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
                [
                    'title'  => 'کنترل حین تولید',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => [
                        [
                            'file'  => '/api/dl-file?path=torque-reports/torque-report.zip',
                            'title' => 'گزارش گشتاور سنجی'
                        ],
                    ]
                ],
                [
                    'title'  => 'ایرادات کیفی '.$qcChecked,
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
                    'values' => ProductionTestResult::query()->where('vin', $vehicle->vin)->get()->map(function ($record) {
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
                            $vehicle->data['PDI_Result'],
                            $vehicle->data['PDI_Result_Description'].' '.$vehicle->data['PDI_Result_Description_2'],
                            (is_numeric(strpos($vehicle->data['PDI_Result'],'Y')) or is_numeric(strpos($vehicle->data['PDI_Result'],'G'))) ?
                                ($r =$vehicle->tracking()->where('model_type', SaleLocation::class)->latest()->first()) ?
                                    'تاریخ بررسی و رفع ایراد: '.
                                    jdate($r->created_at)->toDateString()
                                    :''
                                : ''
                        ]
                    ]
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
                    'values' => BillOfLadingItem::query()
                        ->where('model_id', $vehicle->getAttribute('vin'))
//                        ->whereDoesntHave('billOfLading', function ($q) {
//                            $q->where('location_source_id', 4);
//                            $q->orWhere('location_destination_id', 4);
//                        })
                        ->get()->map(function ($record) {
                            return [
                                jdate($record->billOfLading->created_at)->toDateTimeString(),
                                $record->billOfLading->locationSource->title,
                                $record->billOfLading->locationDestination->title
                            ];
                        })->all()
                ],
                [
                    'title'  => 'آرشیو فنی محصولات',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $copList
                ],
                $police,
                [
                    'title'  => 'ویژگی های فنی و عمومی محصول',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $qrLinks
                ],
            ], $services)))
        ]);
    }
}
