<?php

namespace App\Exports;

use App\Events\JobProcessEvent;
use App\Models\Screw;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class VehicleTorqueTestReportExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize, WithEvents
{
    public $screws;
    private $colorMapping = [];
    private $columnMapping = [];

    public function __construct(private readonly Collection $data, private array $eventData)
    {
        $t = $this->data;
        if (!$t){
            $this->screws = collect();
        }
        $this->screws = Screw::query()
            ->whereHas('carModels', function ($query) use ($t) {
                $query->where('id', $t->first()->vehicle->car_model_id);
            })
            ->get();

        foreach ($this->data as $item) {
            $this->colorMapping[$item->screw_id] = [
                'color' => str_replace('#', '', $item->range_code)
            ];
        }

        $columnIndex = 2;
        foreach ($this->screws as $screw) {
            $this->columnMapping[$screw->id] = $columnIndex++;
        }
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);

                // Apply styles to header row
                $event->sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['argb' => 'FF3490dc'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Apply styles to data cells
                $highestRow = $sheet->getHighestRow();
                $event->sheet->getStyle('A2:'.$sheet->getHighestColumn().$highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // Color cells based on screw_id
                foreach ($this->data as $index => $item) {
                    $rowNumber = $index + 2; // +2 because of header row and 1-based index

                    if (isset($this->columnMapping[$item->screw_id])) {
                        $columnLetter = Coordinate::stringFromColumnIndex($this->columnMapping[$item->screw_id]);
                        $color = $this->colorMapping[$item->screw_id]['color'] ?? 'FFFFFF';
                        $contrastColor = $this->getContrastColor($color);

                        $event->sheet->getStyle($columnLetter.$rowNumber)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => ['argb' => 'FF'.$color],
                            ],
                            'font' => [
                                'color' => ['argb' => 'FF'.$contrastColor],
                            ]
                        ]);
                    }
                }

                // Freeze header row
                $sheet->freezePane('A2');
            },
        ];
    }


    private function getContrastColor($hexColor): string
    {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return ($luminance > 0.5) ? '000000' : 'FFFFFF';
    }

    public function headings(): array
    {
        return array_merge(
            ['تاریخ ثبت'],
            $this->screws->pluck('title')->toArray(),
        );
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function map($row): array
    {
        $rowData = [
            jdate($row->created_at)->format('Y-m-d'),
        ];

        foreach ($this->screws as $screw) {
            $rowData[] = ($row->screw_id == $screw->id) ? $row->value : '';
        }


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
