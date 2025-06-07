<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\Element;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Exception;


class ElementImport implements ToCollection, WithStartRow, WithMapping
{

    public function __construct(public $data)
    {

    }

    public function map($row): array
    {
        $columns = [];
        foreach (Element::HEADING as $key => $value) {
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

    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {
            try {
                if (!Element::query()->where('code', $row['code'])->exists()) {
                   $element = Element::query()->create($row->toArray());
                   $element->makeCodes()->attach($this->data['makeCodes']);
                    $this->data['success']++;

                    $row['result'] = 'imported';
                } else {
                    $this->data['error']++;
                    $row['result'] = 'not imported';
                }
            } catch (Exception $exception) {
                $this->data['error']++;
                $row['result'] = $exception->getMessage();
                logger($exception->getMessage());
            }

            $this->data['done']++;
            $this->sendEvent();
        });
        $uuid = Str::uuid();
        Excel::store(new ResultExport($collection->prepend(Element::HEADING)), 'public/results/' . $uuid . '.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file' => route('dl', ['path' => 'results/' . $uuid . '.xlsx']),
                'total' => $this->data['total'],
                'success' => $this->data['success'],
                'error' => $this->data['error'],
            ]
        ]);

    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }
}
