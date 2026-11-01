<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class TrackingExitDoorReportExport extends DefaultValueBinder implements FromCollection, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Collection $data, private array $eventData) {}

    /**
     * @return mixed
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestDataRow();
                $range = 'A1:I'.$lastRow;

                $sheet->setRightToLeft(true);
                $sheet->setShowGridlines(false);
                $sheet->freezePane('C2');
                $sheet->setAutoFilter($range);
                $sheet->getTabColor()->setRGB('1F4E78');
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['name' => 'Arial', 'size' => 11, 'color' => ['rgb' => '1F2937']],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(34);

                if ($lastRow > 1) {
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(30);
                    }

                    $body = 'A2:I'.$lastRow;
                    $stripe = new Conditional;
                    $stripe->setConditionType(Conditional::CONDITION_EXPRESSION)
                        ->addCondition('MOD(ROW(),2)=0');
                    $stripe->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F1F5F9');
                    $sheet->getStyle($body)->setConditionalStyles([$stripe]);

                    foreach (['B', 'D', 'F', 'I'] as $column) {
                        $style = $sheet->getStyle($column.'2:'.$column.$lastRow);
                        $style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
                        $style->getAlignment()->setReadOrder(Alignment::READORDER_LTR)
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                    $sheet->getStyle('G2:G'.$lastRow)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)->setFitToHeight(0)
                    ->setRowsToRepeatAtTopByStartAndEnd(1, 1)
                    ->setPrintArea($range);
                $sheet->setSelectedCell('A1');
            },
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'شناسه', 'VIN', 'عنوان مدل خودرو', 'کد مرجع مدل خودرو',
            'عنوان برند', 'کد مرجع برند', 'نوع تردد', 'کاربر', 'زمان ثبت',
        ];
    }

    public function title(): string
    {
        return 'گزارش تردد درب خروج';
    }

    public function columnWidths(): array
    {
        return ['A' => 12, 'B' => 25, 'C' => 30, 'D' => 24, 'E' => 26, 'F' => 22, 'G' => 14, 'H' => 28, 'I' => 25];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        // Preserve identifiers (including leading zeros) and prevent formula injection.
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            $cell->getIgnoredErrors()->setNumberStoredAsText(true);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function map($row): array
    {
        $row = [
            $row->id,
            $row->vin,
            $row->vehicle?->carModel?->title ?? '',
            $row->vehicle?->carModel?->reference ?? '',
            $row->vehicle?->makeCode?->title ?? '',
            $row->vehicle?->makeCode?->reference ?? '',
            match ($row->type) {
                'enter' => 'ورود',
                'exit' => 'خروج',
                default => $row->type,
            },
            $row->user?->name ?? '',
            $row->created_at ? jdate($row->created_at)->format('Y/m/d H:i:s') : '',
        ];

        $this->eventData['done']++;
        $this->eventData['success']++;
        $this->sendEvent();

        return $row;
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->eventData));
    }
}
