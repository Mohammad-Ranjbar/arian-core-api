<?php

namespace App\Imports;

use App\Events\ErpJobEvent;
use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BlockItem;
use App\Models\ErpJob;
use App\Models\Job;
use Exception;
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

            try {
                $blockItem = BlockItem::query()->where('id', $row[0])->first();
                if (!empty($blockItem) && $blockItem->getAttribute('unblocked_at') == null) {

                    $blockItem->update([
                        'unblocked_at' => now(),
                        'unblocked_by' => $this->data['user_id'],
                    ]);

                    $count = BlockItem::query()
                        ->where('vin', $blockItem->getAttribute('vin'))
                        ->whereNull('unblocked_at')->count();

                    if ($count === 0) {
                        $parameters = ['barcodeInfoP' => $blockItem->getAttribute('vin')];
                        $erpJob = ErpJob::query()->create([
                            'model_id'   => $blockItem->getAttribute('id'),
                            'model_type' => BlockItem::class,
                            'section'    => 'unblock-vehicle',
                            'user_id'    => $this->data['user_id'],
                            'method'     => 'UnblockingVehicle',
                            'url'        => config('erp.block_unblock'),
                            'parameters' => $parameters,
                            'queue'      => ErpJob::QUEUE_UNIQUE
                        ]);
                        ErpJobEvent::dispatch($erpJob);
                    }


                    $this->data['success']++;

                    $row['result'] = 'imported';
                } else {
                    $this->data['error']++;
                    $row['result'] = 'not imported';
                }
            } catch (Exception $exception) {
                $this->data['error']++;
                $row['result'] = $exception->getMessage();
            }

            $this->data['done']++;
            $this->sendEvent();
        });
        $uuid = Str::uuid();
        Excel::store(new ResultExport($collection->prepend(BlockItem::HEADING)), 'public/results/'.$uuid.'.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file'    => route('dl', ['path' => 'results/'.$uuid.'.xlsx']),
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
