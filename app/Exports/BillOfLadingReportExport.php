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
            'شرکت ترابری',
            'نام راننده',
            'موبایل راننده',
            'کد ملی راننده',
            'شماره بارنامه',
            'آیتم های بارنامه',
            'شماره بچ',
            'شماره درخواست',
            'شماره کوتاژ',
            'وزن',
            'توضیحات',
            'تاریخ ایجاد',
            'ساعت ایجاد',
            'تاریخ ورود',
            'ساعت ورود',
            'تاریخ رسید موقت',
            'ساعت رسید موقت',
            'تاریخ تخلیه',
            'ساعت تخلیه',
            'تاریخ بازگشت کانتینر',
            'ساعت بازگشت کانتینر',
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
//        $deliverDate = $row->actionLogs()->where('action', 'temporary_receipt')->first();
        $TemporaryReceiptDate = $row->statusTime('temporary_receipt');
//        $dischargeDate = $row->actionLogs()->where('action', 'discharge')->first();
        $enterDate = $row->statusTime('enter');
        $dischargeDate = $row->statusTime('discharge');
        $returnContainerDate = $row->statusTime('return_container');
        $row = [
            $row->car->transitCompany->title,
            $row->car->driver->name,
            $row->car->driver->phone,
            $row->car->driver->national_code,
            $row->code,
            $row->vehicles()->get()->implode('vin', ',') . $row->containers()->get()->implode('code', ','),
            $row->batch,
            $row->request_no,
            $row->cottage,
            $row->weight,
            $row->description,
            jdate($row->created_at)->format('Y-m-d'),
            jdate($row->created_at)->format('H:i:s'),
            $enterDate ? jdate($enterDate->created_at)->format('Y-m-d') : '',
            $enterDate ? jdate($enterDate->created_at)->format('H:i:s') : '',
            $TemporaryReceiptDate ? jdate($TemporaryReceiptDate->created_at)->format('Y-m-d') : '',
            $TemporaryReceiptDate ? jdate($TemporaryReceiptDate->created_at)->format('H:i:s') : '',
            $dischargeDate ? jdate($dischargeDate->created_at)->format('Y-m-d') : '',
            $dischargeDate ? jdate($dischargeDate->created_at)->format('H:i:s') : '',
            $returnContainerDate ? jdate($returnContainerDate->created_at)->format('Y-m-d') : '',
            $returnContainerDate ? jdate($returnContainerDate->created_at)->format('H:i:s') : '',

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
