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
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AlternativeReportExport extends DefaultValueBinder implements FromCollection, WithColumnWidths, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Collection $rows, private array $eventData) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'گزارش الترناتیو';
    }

    public function headings(): array
    {
        return ['کد قطعه', 'شرح قطعه', 'شماره سند', 'شماره سفارش تولید', 'تعداد ثبت‌شده', 'تعداد بسته‌بندی', 'واریانت و تعداد', 'وضعیت', 'کاربر', 'تاریخ ثبت', 'تاریخ ارسال', 'تاریخ موفقیت', 'تاریخ خطا'];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 34, 'C' => 27, 'D' => 25, 'E' => 18, 'F' => 18, 'G' => 40, 'H' => 20, 'I' => 25, 'J' => 23, 'K' => 23, 'L' => 23, 'M' => 23];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function map($row): array
    {
        $this->eventData['done']++;
        $this->eventData['success']++;
        event(new JobProcessEvent($this->eventData));
        $date = fn ($value) => $value ? jdate($value)->format('Y/m/d H:i:s') : '';

        return [
            $row->item_no, $row->alternativeItem?->erpProductItem?->title ?? '',
            $row->document_number ?? '', $row->production_order_number ?? '', $row->counter,
            $row->alternativeItem?->packing_qty, $row->variants->map(fn ($v) => $v->variant_code.' : '.$v->quantity)->implode("\n"),
            match ($row->status) {
                'success' => 'موفق', 'failed' => 'ناموفق', 'send' => 'ارسال', default => $row->status ?? ''
            },
            $row->user?->name ?? '', $date($row->created_at), $date($row->send_at), $date($row->success_at), $date($row->failed_at),
        ];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $last = $sheet->getHighestDataRow();
            $range = 'A1:M'.$last;
            $sheet->setRightToLeft(true);
            $sheet->setShowGridlines(false);
            $sheet->freezePane('C2');
            $sheet->setAutoFilter($range);
            $sheet->getTabColor()->setRGB('176B73');
            $sheet->getStyle($range)->applyFromArray(['font' => ['name' => 'Arial', 'size' => 11, 'color' => ['rgb' => '263747']], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_RIGHT, 'wrapText' => true]]);
            $sheet->getStyle('A1:M1')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '176B73']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getRowDimension(1)->setRowHeight(36);
            if ($last > 1) {
                $stripe = new Conditional;
                $stripe->setConditionType(Conditional::CONDITION_EXPRESSION)->addCondition('MOD(ROW(),2)=0');
                $stripe->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EDF6F5');
                $sheet->getStyle('A2:M'.$last)->setConditionalStyles([$stripe]);
                for ($i = 2; $i <= $last; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(-1);
                }
            }
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(1, 1)->setPrintArea($range);
            $sheet->setSelectedCell('A1');
        }];
    }
}
