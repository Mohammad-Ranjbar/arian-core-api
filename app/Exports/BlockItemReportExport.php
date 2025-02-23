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

    /**
     * @param  Collection  $data
     * @param  array  $eventData
     */
    public function __construct(private readonly Collection $data, private array $eventData)
    {
    }

    /**
     * @return mixed
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            "id", "بلاک توسط", "آنبلاک توسط", "دلیل بلوکه", "زمان آنبلاک", "توضیحات", "VIN", "makeCode", "carModel","زمان ایجاد"
        ];
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @param $row
     * @return array
     */
    public function map($row): array
    {
        $row = [
            $row->id,
            $row->blockedBy ? $row->blockedBy->name : '',
            $row->unblockedBy ? $row->unblockedBy->name : '',
            $row->blockReason ?$row->blockReason->title : '',
            $row->unblocked_at ? jdate($row->unblocked_at) : '',
            $row->description,
            $row->vin,
            $row->vehicle->makeCode->reference,
            $row->vehicle->carModel->reference,
            jdate($row->created_at),
        ];

        $this->eventData['done']++;
        $this->eventData['success']++;
        $this->sendEvent();

        return $row;
    }

    /**
     * @return void
     */
    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->eventData));
    }
}
