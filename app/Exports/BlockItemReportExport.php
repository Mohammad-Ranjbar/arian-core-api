<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class BlockItemReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{

    public function __construct(private readonly Collection $data, private array $eventData)
    {
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }

    public function headings(): array
    {
        return ["id", "بلاک توسط", "آنبلاک توسط", "دلیل توقف", "زمان آنبلاک", "توضیحات", "VIN", "زمان ایجاد"];
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function map($row): array
    {
        $row = [
            $row->id,
            $row->blockedBy ? $row->blockedBy->name : '',
            $row->unblockedBy ? $row->unblockedBy->name : '',
            $row->blockReason->title,
            jdate($row->unblocked_at),
            $row->description,
            $row->vin,
            jdate($row->created_at),
        ];

        $this->eventData['done'] ++;
        $this->eventData['success'] ++;
        $this->sendEvent();

        return $row;
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->eventData));
    }
}
