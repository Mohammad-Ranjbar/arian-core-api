<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ResultExport implements FromCollection, ShouldAutoSize
{
    /**
     * @param $collection
     */
    public function __construct(public $collection)
    {
    }


    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->collection;
    }
}
