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

class GatePermitReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{

    /**
     * @param Collection $data
     * @param array $eventData
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
            'VIN',
            'نوع',
            'مکان',
            'نوع ثبت کننده',
            'مشخصات ثبت کننده',
            'نوع مجوز',
            'تاریخ ثبت',
            'ساعت ثبت',

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
        $type = '';
        if ($row->type == 'exit') {
            $type = 'خروج';
        } elseif ($row->type == 'enter') {
            $type = 'ورود';
        }
        $row = [
            $row->model_id,
            $type,
            $row->location?->title ?? 'نامشخص',
            $row->owner?->getTable,
            $row->owner?->name ?? 'نامشخص',
            $row->exit_type == 2 ? 'امانی' : 'قطعی',
            jdate($row->created_at)->format('Y-m-d'),
            jdate($row->created_at)->format('H:i:s'),
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
