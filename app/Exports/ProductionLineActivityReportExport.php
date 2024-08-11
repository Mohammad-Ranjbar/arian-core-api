<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ProductionLineActivityReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
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
            "id",
            "خط تولید",
            "ایستگاه",
            "عامل توقف",
            "دلیل توقف",
            "نوع توقف",
            "توضیحات",
            "نوت",
            "تاریخ پایان توقف",
            "ساعت پایان توقف",
            "تاریخ شروع توقف",
            "ساعت شروع توقف",
            "زمان موثر",
            "زمان ایجاد",
            "مدت توقف",
            "قطعا مصرفی",
            "مدت توقف",
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
        $totalDuration = 0;
        if (isset($row->stop_at) && isset($row->start_at)){
            $startTime = Carbon::parse($row->stop_at);
            $finishTime = Carbon::parse($row->start_at);
            $totalDuration = $finishTime->diffInMinutes($startTime);
        }
        $row = [
            $row->id,
            $row->station->workGroupCenter->ProductionLine->title,
            $row->station->title,
            $row->stopReason->fact->title,
            $row->stopReason->title,
            $row->stopType->title,
            $row->description,
            $row->note,
            $row->start_at ? jdate($row->start_at)->format('Y-m-d') : '',
            $row->start_at ? jdate($row->start_at)->format('H:i:s') : '',
            $row->stop_at ? jdate($row->stop_at)->format('Y-m-d') : '',
            $row->stop_at ? jdate($row->stop_at)->format('H:i:s') : '',
            $row->effective_time,
            jdate($row->created_at),
            $totalDuration,
            $row->productItems()->get()->implode('title', ' , '),
            ($row->start_at && $row->stop_at ) ? Carbon::make($row->start_at)->diffInMinutes($row->stop_at) : '',
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
