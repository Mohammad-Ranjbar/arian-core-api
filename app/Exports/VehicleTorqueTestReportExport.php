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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VehicleTorqueTestReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{

    /**
     * @param Collection $data
     * @param array $eventData
     */
    public function __construct(private readonly Collection $data, private array $eventData)
    {
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);

                $highestRow = $event->sheet->getDelegate()->getHighestRow();

                for ($row = 2; $row <= $highestRow; $row++) {
                    $value = $event->sheet->getCell('B' . $row)->getValue();
                    $color = $this->getColorBasedOnValue($value);

                    $event->sheet->getDelegate()->getStyle('B' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'color' => ['argb' => $color],
                        ]
                    ]);
                }
            },
        ];
    }

    private function getColorBasedOnValue($value): string
    {
        if ($value > 100) {
            return 'FF0000'; // Red
        } elseif ($value > 50) {
            return 'FFFF00'; // Yellow
        }
        return '00FF00'; // Green
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'id',
            'مقدار',
            'vin',
            ' پیچ',
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
            $row->value,
            $row->vin,
            $row->screw->title,
            $row->created_at,
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
