<?php

namespace App\Imports;

use App\Models\Incident;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class IncidentImport implements ToModel,WithStartRow
{
    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        return new Incident([
            'title'   => $row[0],
            'title_en'   => $row[1],
            'code'    => $row[2],
        ]);
    }
}
