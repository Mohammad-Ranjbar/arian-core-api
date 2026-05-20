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

class VehicleTorqueTestReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{

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


    public function headings(): array
    {
        return array_merge(
            [
                'VIN',
                'تورک متر',
                'وسیله اندازه گیری',
                'اتصال',
                'مقدار',
                'کاربر',
                'توضیحات',
                'تاریخ ثبت',
            ],
        );
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function map($row): array
    {
        $rowData = [
            $row->vin,
            $row->torqueMeter ? $row->torqueMeter->title . '::' . $row->torqueMeter->code : '',
            $row->torqueMeasuringEquipment?->title ?? '',
            $row->screw?->title ?? '',
            $row->value,
            $row->user->name ,
            jdate($row->created_at)->format('Y-m-d H:i:s'),
        ];


        $this->eventData['done']++;
        $this->eventData['success']++;
        $this->sendEvent();

        return $rowData;
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->eventData));
    }


}
