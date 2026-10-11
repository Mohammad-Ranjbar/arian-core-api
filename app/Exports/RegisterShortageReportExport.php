<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use App\Models\RegisterShortage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegisterShortageReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(private readonly Collection $data, private array $eventData) {}

    public function headings(): array
    {
        return [
            'ردیف',
            'VIN',
            'نوع خودرو',
            'کد قطعه',
            'نام قطعه',
            'نوع آیتم',
            'تعداد کسری',
            'وضعیت کسری',
            'کاربر ثبت کننده',
            'تاریخ ثبت کسری',
            'تاریخ رفع کسری',
            'رفع کسری توسط',
        ];
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function map($row): array
    {
        $this->rowNumber++;

        $mapped = [
            $this->rowNumber,
            $row->vin,
            $row->carModel?->title ?? $row->carModel?->reference ?? '',
            $row->item_no,
            $row->item_description ?? '',
            match (true) {
                $row->register_shortage_id !== null => 'وابسته',
                $row->type === RegisterShortage::TYPE_MAIN => 'اصلی',
                default => 'منفرد',
            },
            $row->qty,
            match ($row->status) {
                RegisterShortage::STATUS_HAS_SHORTAGE => 'کسری دارد',
                RegisterShortage::STATUS_RESOLVED => 'رفع کسری شده',
                default => $row->status,
            },
            $row->user?->name ?? '',
            jdate($row->created_at)->format('Y/m/d H:i'),
            $row->resolved_at ? jdate($row->resolved_at)->format('Y/m/d H:i') : '',
            $row->resolvedBy?->name ?? '',
        ];

        $this->eventData['done']++;
        $this->eventData['success']++;
        event(new JobProcessEvent($this->eventData));

        return $mapped;
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'G' => '#,##0.00',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['argb' => 'FF1F64F2'],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->getStyle($sheet->calculateWorksheetDimension())
                    ->getAlignment()
                    ->setVertical('center');
            },
        ];
    }
}
