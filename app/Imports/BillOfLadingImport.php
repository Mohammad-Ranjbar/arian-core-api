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

            if (!BillOfLading::query()->where('code ', $row[7])->exists()) {

                if (!$driver = Driver::query()->where('national_code', $row[6])->first()) {
                    $driver = Driver::query()->create([
                        'national_code' => $row[6],
                        'phone'         => $row[5],
                        'name'          => $row[4],
                    ]);
                }
                if (!$transitCompany = TransitCompany::query()->where('title', $row[1])->first()) {
                    $transitCompany = TransitCompany::query()->create([
                        'title' => $row[1],
                    ]);
                }
                if (!$car = Car::query()->where('plate', $row[3])->first()) {
                    $car = Car::query()->create([
                        'plate'              => $row[3],
                        'car'                => $row[2],
                        'transit_company_id' => $transitCompany->getAttribute('id'),
                        'driver_id'          => $driver->getAttribute('id'),
                    ]);
                }
                BillOfLading::query()->create([
                    'user_id'                 => Auth::id(),
                    'location_source_id'      => request()->get('location_source_id'),
                    'location_destination_id' => request()->get('location_destination_id'),
                    'car_id'                  => $car->getAttribute('id'),
                    'code'                    => $row[7],
                    'request_no'              => $row[8],
                    'status'                  => 'new',
                    'priority'                => 0,
                    'cottage'                 => $row[9],
                    'weight'                  => $row[10],
                    'batch'                   => $row[11],
                    'description'             => $row[12],


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
