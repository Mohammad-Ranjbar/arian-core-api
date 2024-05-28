<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use App\Models\Barcode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class BarcodeReportExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithEvents,WithCustomChunkSize
{


    public function __construct(private readonly array $data, private array $eventData)
    {

    }

    public function query()
    {
        return Barcode::getReportData($this->data);
    }

    public function chunkSize(): int
    {
        return 100;
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
            "VIN", "کاربر","کد", "زمان",
        ];
    }



    /**
     * @param $row
     * @return array
     */
    public function map($row): array
    {
        $row = [
            $row->vin,
            $row->user ? $row->user->name : '',
            $row->code,
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
