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

class BillOfLadingReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
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
            'VIN/Container',
            'shipping_company',
            'vehicle_title',
//            'number_plates',
//            'plate_area',
            'driver_name',
            'driver_mobile',
            'driver_national_code',
            'freight_number',
            'request_number',
            'cottage_number',
            'weight',
            'batch',
            'description',
            'created_at',
            'deliver_date',
            'discharge_date',
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
        $deliverDate = $row->actionLogs()->where('action', 'temporary_receipt')->first();
        $dischargeDate = $row->actionLogs()->where('action', 'discharge')->first();
        $row = [
            $row->vehicles()->get()->implode('vin', ',') . $row->containers()->get()->implode('code', ','),
            $row->car->transitCompany->title,
            $row->car->title,
            $row->car->driver->name,
            $row->car->driver->phone,
            $row->car->driver->national_code,
            $row->code,
            $row->request_no,
            $row->cottage,
            $row->weight,
            $row->batch,
            $row->description,
            jdate($row->created_at),
            $deliverDate ? jdate($deliverDate->created_at) : '',
            $dischargeDate ? jdate($dischargeDate->created_at) : '',
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
