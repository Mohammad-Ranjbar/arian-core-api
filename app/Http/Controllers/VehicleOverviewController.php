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
            ]
        ];
        $copList = [];

        if (str_contains(strtolower($vehicle->getAttribute('data')['Model_No']), 'lamari')) {
            $copList = [
                [
                    'file'  => '/api/dl-file?path=cop/lamari/1.pdf',
                    'title' => 'چراغ پلاک عقب'
                ]
            ];
        }
        if (str_contains(strtolower($vehicle->getAttribute('data')['Model_No']), 'dumper')) {
            $copList = [
                [
                    'file'  => '/api/dl-file?path=cop/dumper/1.pdf',
                    'title' => 'استحكام صندلي ونگهدارنده هاي آن 6503'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/2.pdf',
                    'title' => 'برجستگيهاي بيروني كابين'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/3.pdf',
                    'title' => 'تراز صدا'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/4.pdf',
                    'title' => 'ترمز اضطراری پیشرفته'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/5.pdf',
                    'title' => 'تکیه گاه'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/6.pdf',
                    'title' => 'جرم و ابعاد1.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/7.pdf',
                    'title' => 'حفاظ عرضي زير شاسي جلو'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/8.pdf',
                    'title' => 'حفاظت از خودرو در برابر استفاده غیر مجاز'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/9.pdf',
                    'title' => 'حفاظت از سرنشینان کابین وسیله نقلیه تجاری'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/10.pdf',
                    'title' => 'دسترسی به وسیله نقلیه و قدرت مانور'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/11.pdf',
                    'title' => 'سیستم ترمز وسایل نقلیه'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/12.pdf',
                    'title' => 'سیستم هشدار انحراف از مسیر'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/13.pdf',
                    'title' => 'سیستم گرمایش'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/14.pdf',
                    'title' => 'شب نما.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/15.pdf',
                    'title' => 'شيشه هاي ايمني'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/16.pdf',
                    'title' => 'قلاب بكسل بند.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/17.pdf',
                    'title' => 'كنترلها ، خبردهنده ها ، نشانگرها'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/18.pdf',
                    'title' => 'محدود كننده سرعت'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/19.pdf',
                    'title' => 'موقعيت نصب پلاك.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/20.pdf',
                    'title' => 'نصب تایر'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/21.pdf',
                    'title' => 'نصب وسايل روشنايي و علامت دهنده هاي نوري.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/22.pdf',
                    'title' => 'وسايل هشداردهنده شنيداري.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/23.pdf',
                    'title' => 'وسایل دید غیر مستقیم و نصب آنها.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/24.pdf',
                    'title' => 'وسایل سرعت سنج'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/25.pdf',
                    'title' => 'پلاك شناسايي (Plates).'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/26.pdf',
                    'title' => 'چراغ دنده عقب'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/27.pdf',
                    'title' => 'چراغ رانندگي در روز'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/28.pdf',
                    'title' => 'چراغ راهنما.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/29.pdf',
                    'title' => 'چراغ مه شکن جلو'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/30.pdf',
                    'title' => 'چراغ مه شکن عقب'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/31.pdf',
                    'title' => 'چراغ نشانگر جانبی.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/32.pdf',
                    'title' => 'چراغ هاي موقعیت جلو و عقب ، ترمز و منتهی الیه'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/33.pdf',
                    'title' => 'چراغ های جلوی وسایل نقلیه موتوری'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/34.pdf',
                    'title' => 'چراغ پلاك عقب.'
                ],
                [
                    'file'  => '/api/dl-file?path=cop/dumper/35.pdf',
                    'title' => 'کمربندهای ایمنی و سیستمهای نگهدارنده.'
                ]
            ];
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
                        'تاریخ سند',
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
                    'title'  => 'اطلاعات و مشخصات',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $qrLinks
                ],
                [
                    'title'  => 'رهگیری محصول',
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
                    'title'  => 'COP PLAN',
                    'type'   => 'download-list',
                    'heads'  => [],
                    'values' => $copList
                ],
                [
                    'title'  => 'آزمون های ثبت شده',
                    'type'   => 'table',
                    'heads'  => [],
                    'values' => []
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
