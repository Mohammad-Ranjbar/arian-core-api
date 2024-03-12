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
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class BillOfLadingImport implements ToCollection, WithStartRow
{

    /**
     * @param $data
     */
    public function __construct(public $data)
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {

            if (!BillOfLading::query()->where('code', $row[8])->exists()) {

                if (!$driver = Driver::query()->where('national_code', $row[7])->first()) {
                    $driver = Driver::query()->create([
                        'national_code' => $row[7],
                        'phone'         => $row[6],
                        'name'          => $row[5],
                    ]);
                }
                if (!$transitCompany = TransitCompany::query()->where('title', $row[1])->first()) {
                    $transitCompany = TransitCompany::query()->create([
                        'title' => $row[1],
                    ]);
                }
                $nums = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
                $chr = str_replace($nums, null, $row[3]);
                $one = substr($row[3], 0, 2);
                $second = substr($row[3], 2, 3);
                $plate = [
                    "char"    => $chr,
                    "number1" => $one,
                    "number2" => $second,
                    "number3" => $row[4]
                ];
                if (!$car = Car::query()->where('plate', $plate)->first()) {
                    $car = Car::query()->create([
                        'plate'              => $plate,
                        'car'                => $row[2],
                        'transit_company_id' => $transitCompany->getAttribute('id'),
                        'driver_id'          => $driver->getAttribute('id'),
                    ]);
                }
                BillOfLading::query()->create([
                    'user_id'                 => Auth::id(),
                    'location_source_id'      => $this->data['location_source_id'],
                    'location_destination_id' => $this->data['location_destination_id'],
                    'car_id'                  => $car->getAttribute('id'),
                    'code'                    => $row[8],
                    'request_no'              => $row[9],
                    'status'                  => 'new',
                    'priority'                => 0,
                    'cottage'                 => $row[10],
                    'weight'                  => $row[11],
                    'batch'                   => $row[12],
                    'description'             => $row[13],
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
            $headers = [
                'vin',
                'شرکت حمل',
                'عنوان خودرو',
                'شماره پلاک',
                'منطقه پلاک',
                'نام راننده',
                'شماره همراه راننده',
                'کدملی راننده',
                'شماره بارنامه',
                'شماره درخواست',
                'شماره کوتاژ',
                'وزن',
                'بچ',
                'توضیحات',
            ];
        } else {
            $headers = [
                'شماره کانتینر',
                'شرکت حمل',
                'عنوان خودرو',
                'شماره پلاک',
                'منطقه پلاک',
                'نام راننده',
                'شماره همراه راننده',
                'کدملی راننده',
                'شماره بارنامه',
                'شماره درخواست',
                'شماره کوتاژ',
                'وزن',
                'بچ',
                'توضیحات',
            ];
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
