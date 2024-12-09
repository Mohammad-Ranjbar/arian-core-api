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

class FaultReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
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
            "VIN",
            "کاربر",
            "متد",
            "شدت",
            "کد آسیب",
            "عنوان آسیب",
            "کد قطعه",
            "عنوان قطعه",
            "زمان بررسی",
            "زمان",
            "توضیحات",
            "لوکیشن",
            "دسته بندی ایراد",
            "تولید",
            "نوع تولید",
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
            $row->vin,
            $row->user?->name ?? '',
            $row->method,
            $row->intensity,
            $row->incident?->code ?? '',
            $row->incident?->title ?? '',
            $row->element?->code ?? '',
            $row->element?->title ?? '',
            jdate($row->checked_at),
            jdate($row->created_at),
            $row->description,
            $row->customLocation->title,
            $row->incidentCategory?->title ?? '',
            $row->vin_type,
            $row->production_type,
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
