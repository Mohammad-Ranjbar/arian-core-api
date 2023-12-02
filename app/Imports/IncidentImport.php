<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Models\Incident;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;


class IncidentImport implements ToCollection, WithStartRow, WithMapping
{

    public function __construct(public $data)
    {

    }

    public function map($row): array
    {
        $columns = [];
        foreach (Incident::HEADING as $key => $value) {
            $columns[$value] = $row[$key];
        }
        return $columns;
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $collection): array
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {
            if (!Incident::query()->where('code', $row['code'])->exists()) {
                Incident::query()->create($row->toArray());
                $this->data['success']++;
            } else {
                $this->data['error']++;
            }
            $this->data['done']++;
            $this->sendEvent();
        });

        return [

        ];
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }
}
