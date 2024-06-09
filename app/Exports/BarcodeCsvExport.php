<?php

namespace App\Exports;


use App\Events\JobProcessEvent;
use App\Models\Barcode;

class BarcodeCsvExport
{

    public $handle;
    public $eventData;

    public function make($data, $path, $eventData): void
    {
        $query = Barcode::getReportData($data);

        $eventData['total'] = $query->count();

        $this->eventData = $eventData;

        $this->sendEvent();

        $this->handle = fopen(storage_path($path), 'w');

        fwrite($this->handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $query->chunk(10000, function ($rows) {
            foreach ($rows as $row) {
                fputcsv($this->handle, [
                    $row->user->name,
                    $row->vin,
                    $row->code,
                    $row->erpProductItem?->title ?? null,
                    jdate($row->created_at),
                ]);
                $this->eventData['done']++;
                $this->eventData['success']++;
                $this->sendEvent();
            }
        });
        fclose($this->handle);
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->eventData));
    }
}
