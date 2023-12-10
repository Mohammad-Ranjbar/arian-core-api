<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TemplateExport implements FromCollection, WithTitle, WithHeadings
{
    protected array $headers;

    public function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    public function collection(): Collection
    {
// Return an empty collection as no data is being exported
        return new Collection();
    }

    public function title(): string
    {
// Return the sheet title
        return 'Sheet1';
    }

    public function headings(): array
    {
// Return the specified column headings
        return $this->headers;
    }
}
