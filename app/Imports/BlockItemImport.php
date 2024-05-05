<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BlockItem;
use App\Models\Job;
use App\Models\Vehicle;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class BlockItemImport implements ToCollection, WithStartRow
{


    public function __construct(
        public array           $data,
        public readonly string $description,
        public readonly string $blockReasonId,
        public readonly string $userId
    )
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
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {
            try {
                $vehicle = Vehicle::query()->where('vin', $row[0])->first();
                if ($vehicle) {
                    BlockItem::query()->create([
                        'vin' => $vehicle->getAttribute('vin'),
                        'blocked_at' => now(),
                        'blocked_by' => $this->userId,
                        'description' => $this->description,
                        'block_reason_id' => $this->blockReasonId,
                    ]);

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
        Excel::store(new ResultExport($collection->prepend(BlockItem::HEADING)), 'public/results/' . $uuid . '.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file' => route('dl', ['path' => 'results/' . $uuid . '.xlsx']),
                'total' => $this->data['total'],
                'success' => $this->data['success'],
                'error' => $this->data['error'],
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
