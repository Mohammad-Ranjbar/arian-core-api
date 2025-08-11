<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BomExport implements FromCollection, WithHeadings
{

    public function __construct(private readonly Collection $data)
    {
    }

    public function headings(): array
    {
        return [
            'title',
            'description',
            'reference',
            'Quantity'
        ];
    }

    public function collection(): Collection
    {
        return $this->data;
    }
}
