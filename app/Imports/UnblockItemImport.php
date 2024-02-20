<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BlockItem;
use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class UnblockItemImport implements ToCollection, WithStartRow
{

    /**
     * @param  array  $data
     */
    public function __construct(public array $data)
    {

    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param  Collection  $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {
            $blockItem = BlockItem::query()->where('id', $row[0])->first();
            if ($blockItem) {
                $blockItem->update([
                    'unblocked_at'  => now(),
                    'unblocked_by' => Auth::id(),
                ]);
                $this->data['success']++;

                $row['result'] = 'imported';
            } else {
                $this->data['error']++;
                $row['result'] = 'not imported';
            }
            $this->data['done']++;
            $this->sendEvent();
        });
        $uuid = Str::uuid();
        Excel::store(new ResultExport($collection->prepend(BlockItem::HEADING)), 'public/results/'.$uuid.'.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file'    => asset('storage/results/'.$uuid.'.xlsx'),
                'total'   => $this->data['total'],
                'success' => $this->data['success'],
                'error'   => $this->data['error'],
            ]
        ]);

    }

    /**
     * @return void
     */
    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }
}
