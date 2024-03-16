<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BillOfLading;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Job;
use App\Models\TransitCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class BillOfLadingImport implements ToCollection, WithStartRow, WithMapping
{

    /**
     * @param $data
     */
    public function __construct(public $data)
    {

    }

    /**
     * @param $row
     * @return array
     */
    public function map($row): array
    {
        $columns = [];

        if ($this->data['type'] === 'containers') {
            foreach (BillOfLading::HEAD_CONTAINER as $key => $value) {
                $columns[$value] = $row[$key];
            }
        } elseif ($this->data['type'] === 'vehicles' || $this->data['type'] === 'bodies') {
            foreach (BillOfLading::HEAD_VEHICLE as $key => $value) {
                $columns[$value] = $row[$key];
            }
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


    /**
     * @param  Collection  $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {

            if (!BillOfLading::query()->where('code', $row['freight_number'])->exists()) {

                if (!$driver = Driver::query()->where('national_code', $row['driver_national_code'])->first()) {
                    $driver = Driver::query()->create([
                        'national_code' => $row['driver_national_code'],
                        'phone'         => $row['driver_mobile'],
                        'name'          => $row['driver_name'],
                    ]);
                }
                if (!$transitCompany = TransitCompany::query()->where('title', $row['shipping_company'])->first()) {
                    $transitCompany = TransitCompany::query()->create([
                        'title' => $row['shipping_company'],
                    ]);
                }

                $nums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
                $chr = str_replace($nums, null, $row['number_plates']);
                $one = substr($row['number_plates'], 0, 2);
                $second = substr($row['number_plates'], 2, 3);
                $plate = [
                    "char"    => $chr,
                    "number1" => to_english_numbers($one),
                    "number2" => to_english_numbers($second),
                    "number3" => to_english_numbers($row['plate_area'])
                ];
                if (!$car = Car::query()->where('plate', $plate)->first()) {
                    $car = Car::query()->create([
                        'plate'              => $plate,
                        'car'                => $row['vehicle_title'],
                        'transit_company_id' => $transitCompany->getAttribute('id'),
                        'driver_id'          => $driver->getAttribute('id'),
                    ]);
                }
                BillOfLading::query()->create([
                    'user_id'                 => Auth::id(),
                    'location_source_id'      => $this->data['location_source_id'],
                    'location_destination_id' => $this->data['location_destination_id'],
                    'car_id'                  => $car->getAttribute('id'),
                    'code'                    => $row['freight_number'],
                    'request_no'              => $row['request_number'],
                    'status'                  => 'new',
                    'priority'                => 0,
                    'cottage'                 => $row['cottage'],
                    'weight'                  => $row['weight'],
                    'batch'                   => $row['batch'],
                    'description'             => $row['description'],
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
        if ($this->data['type'] === 'vehicles' || $this->data['type'] === 'bodies') {
            $headers = BillOfLading::HEAD_VEHICLE;
        } else {
            $headers = BillOfLading::HEAD_CONTAINER;
        }
        Excel::store(new ResultExport($collection->prepend($headers)), 'public/results/'.$uuid.'.xlsx');
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
