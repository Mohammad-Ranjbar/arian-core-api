<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use App\Services\TimeService;
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
            'شماره پلاک',
            'کد ملی راننده',
            'شماره بارنامه',
            'container No / Vin',
            'Batch No',
            'شماره درخواست',
            'شماره کوتاژ',
            'وزن',
            'توضیحات',
            'تاریخ ایجاد',
            'ساعت ایجاد',
            'تاریخ ورود به پارکینگ',
            'ساعت ورود به پارکینگ',
            'تاریخ ورود',
            'ساعت ورود',
            'تاریخ تخلیه',
            'ساعت تخلیه',
            'تاریخ خروج کانتینر',
            'ساعت خروج کانتینر',
            'مدت زمان انتظار ورود به کارخانه',
            'مدت زمان تخلیه و خروج',
            'کل زمان حضور',
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
        $TemporaryReceiptDate = $row->statusTime('temporary_receipt');
        $enterDate = $row->statusTime('enter');
        $dischargeDate = $row->statusTime('discharge');
        $returnContainerDate = $row->statusTime('return_container');
        $diffEnterAndTemporaryReceipt = $enterDate && $TemporaryReceiptDate ? TimeService::getDiffTwoTime($enterDate, $TemporaryReceiptDate) : '';
        $diffEnterAndReturnContainer = $enterDate && $returnContainerDate ? TimeService::getDiffTwoTime($enterDate, $returnContainerDate) : '';

        if (!empty($diffEnterAndTemporaryReceipt) && !empty($diffEnterAndReturnContainer)) {
            $result = TimeService::getSumTwoTime($diffEnterAndReturnContainer, $diffEnterAndTemporaryReceipt);
        } else {
            $result = '';
        }
        $plate = json_decode($row->car->plate);
        $row = [
            $row->car->transitCompany->title,
            $row->car->driver->name,
            $row->car->driver->phone,
            $plate?->number3 . $plate?->char . $plate?->number2 . $plate?->number1,
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
            $TemporaryReceiptDate ? jdate($TemporaryReceiptDate)->format('Y-m-d') : '',
            $TemporaryReceiptDate ? jdate($TemporaryReceiptDate)->format('H:i:s') : '',
            $enterDate ? jdate($enterDate)->format('Y-m-d') : '',
            $enterDate ? jdate($enterDate)->format('H:i:s') : '',
            $dischargeDate ? jdate($dischargeDate)->format('Y-m-d') : '',
            $dischargeDate ? jdate($dischargeDate)->format('H:i:s') : '',
            $returnContainerDate ? jdate($returnContainerDate)->format('Y-m-d') : '',
            $returnContainerDate ? jdate($returnContainerDate)->format('H:i:s') : '',
            $diffEnterAndTemporaryReceipt,
            $diffEnterAndReturnContainer,
            $result

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
