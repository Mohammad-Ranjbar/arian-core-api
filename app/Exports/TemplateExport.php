<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class TemplateExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @var array
     */
    protected array $headers;

    /**
     * @param  array  $headers
     */
    public function __construct(array $headers)
    {
        $this->headers = $headers;
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
     * @return Collection
     */
    public function collection(): Collection
    {
// Return an empty collection as no data is being exported
        return new Collection();
    }

    /**
     * @return string
     */
    public function title(): string
    {
// Return the sheet title
        return 'Sheet1';
    }

    /**
     * @return array
     */
    public function headings(): array
    {
// Return the specified column headings
        return $this->headers;
    }
}
