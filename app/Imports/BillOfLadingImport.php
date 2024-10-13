<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BillOfLading;
use App\Models\Car;
use App\Models\Container;
use App\Models\Driver;
use App\Models\GatePermit;
use App\Models\Job;
use App\Models\TransitCompany;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Erp\ErpCreateExitRequest;
use App\Services\Erp\ErpReturnRequest;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class BillOfLadingImport implements ToCollection, WithStartRow, WithMapping
{


    public function __construct(public $data, private $locationSourceID = null, private $locationDestinationID = null, private $userId = null, private string $type = '')
    {

    }

    /**
     * @param $row
     * @return array
     */
    public function map($row): array
    {
        $columns = [];

        $job = Job::query()->find($this->data['id']);
        $this->userId = $job->getAttribute('user_id');
        $data = $job->getAttribute('data');

        $this->type = $data['type'];
        $this->locationSourceID = $data['location_source_id'];
        $this->locationDestinationID = $data['location_destination_id'];
        if ($data['type'] === 'containers') {
            foreach (BillOfLading::HEAD_CONTAINER as $key => $value) {
                $columns[$value] = $row[$key];
            }
        } elseif ($data['type'] === 'vehicles' || $data['type'] === 'bodies') {
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
     * @param Collection $collection
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $this->data['total'] = $collection->count();
        $this->sendEvent();

        $collection->each(function ($row) {

            try {


                //TODO checking by each colmn
                //create gate permit by cody => check vin in vehicle table and chack in data and vehicle type

//                if (empty($row['VIN']) || empty($row['container_number'])) {
//                    throw new Exception('vin or container number is empty');
//                }
                if (!empty($row['VIN'])) {

                    $this->checkingData($row);

                    $billOfLading = BillOfLading::query()->where('code', $row['freight_number'])->first();
                }

                if (!empty($row['container_number'])) {
                    $this->checkingData($row);
                    $container = Container::query()->where('code', $row['container_number'])->firstOrCreate([
                        'code' => $row['container_number'],
                    ]);
                }
                if (!$billOfLading) {

                    if (!$driver = Driver::query()->where('national_code', $row['driver_national_code'])->first()) {
                        $driver = Driver::query()->create([
                            'national_code' => $row['driver_national_code'],
                            'phone' => $row['driver_mobile'],
                            'name' => $row['driver_name'],
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
                        "char" => $chr,
                        "number1" => $one,
                        "number2" => $second,
                        "number3" => $row['plate_area']
                    ];

                    if (!$car = Car::query()->where('plate', $plate)->first()) {
                        $car = Car::query()->create([
                            'plate' => json_encode($plate),
                            'car' => $row['vehicle_title'],
                            'transit_company_id' => $transitCompany->getAttribute('id'),
                            'driver_id' => $driver->getAttribute('id'),
                        ]);
                    }
                    $billOfLading = BillOfLading::query()->create([
                        'user_id' => $this->userId,
                        'location_source_id' => $this->locationSourceID,
                        'location_destination_id' => $this->locationDestinationID,
                        'car_id' => $car->getAttribute('id'),
                        'code' => $row['freight_number'],
                        'request_no' => $row['request_number'],
                        'status' => 'new',
                        'priority' => 0,
                        'cottage' => $row['cottage_number'],
                        'weight' => $row['weight'],
                        'batch' => $row['batch'],
                        'description' => $row['description'],
                        'type' => $this->type
                    ]);

                    if (!empty($row['VIN'])) {
                        if ($billOfLading->type === 'bodies') {
                            if (!GatePermit::query()
                                    ->where('model_type', Vehicle::class)
                                    ->where('model_id', $row['VIN'])->exists()) {

                                    $type = $billOfLading->destination_id == 1 ? 'enter' : 'exit';
                                    $exitType = 1;// by reza => consignment , 1
                                    $gate = GatePermit::query()->create([
                                        'model_type'  => Vehicle::class,
                                        'model_id'    => $item,
                                        'location_id' => $billOfLading->location_destination_id,
                                        'owner_id'    => Auth::id(),
                                        'owner_type'  => User::class,
                                        'type'        => $type,
                                        'exit_type'   => $exitType
                                    ]);

                                    if ($type == 'exit') {
                                        $response = ErpCreateExitRequest::createExitRequest($item, $exitType,
                                            $gate->location->getAttribute('code'));

                                        if ($response['status'] !== 'ok') {
                                            throw new Exception($response['message']);
                                        }
                                    } else {
                                        $response = ErpReturnRequest::returnRequest($item, $gate->location->getAttribute('code'));

                                        if ($response['status'] !== 'ok') {
                                            throw new Exception($response['message']);
                                        }
                                    }
                            }
                        }

                        DB::table('bill_of_lading_item')->insert([
                            'model_id' => $row['VIN'],
                            'model_type' => Vehicle::class,
                            'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                        ]);


                    }
                    if (!empty($row['container_number'])) {
                        DB::table('bill_of_lading_item')->insert([
                            'model_id' => $container->getAttribute('id'),
                            'model_type' => Container::class,
                            'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                        ]);
                    }

                    $this->data['success']++;

                    $row['result'] = 'imported';
                } else {
                    if (!empty($row['VIN'])) {
                        DB::table('bill_of_lading_item')->insert([
                            'model_id' => $row['VIN'],
                            'model_type' => Vehicle::class,
                            'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                        ]);

                    }
                    if (!empty($row['container_number'])) {
                        DB::table('bill_of_lading_item')->insert([
                            'model_id' => $container->getAttribute('id'),
                            'model_type' => Container::class,
                            'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                        ]);
                    }
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
        if ($this->type === 'vehicles' || $this->type === 'bodies') {
            $headers = BillOfLading::HEAD_VEHICLE;
        } else {
            $headers = BillOfLading::HEAD_CONTAINER;
        }
        Excel::store(new ResultExport($collection->prepend($headers)), 'public/results/' . $uuid . '.xlsx');
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
     * @throws Exception
     */
    public function checkingData($row)
    {
        if (empty($row['vehicle_title'])) {
            throw new Exception('vehicle_title is empty');
        }
        if (empty($row['number_plates'])) {
            throw new Exception('number_plates is empty');
        }
        if (empty($row['plate_area'])) {
            throw new Exception('plate_area is empty');
        }
        if (empty($row['driver_name'])) {
            throw new Exception('driver_name is empty');
        }
        if (empty($row['driver_mobile'])) {
            throw new Exception('driver_mobile is empty');
        }
        if (empty($row['driver_national_code'])) {
            throw new Exception('driver_national_code is empty');
        }
        if (empty($row['freight_number'])) {
            throw new Exception('freight_number is empty');
        }
        if (empty($row['request_number'])) {
            throw new Exception('request_number is empty');
        }
        if (empty($row['cottage_number'])) {
            throw new Exception('cottage_number is empty');
        }
        if (empty($row['weight'])) {
            throw new Exception('weight is empty');
        }
        if (empty($row['batch'])) {
            throw new Exception('batch is empty');
        }
    }

    /**
     * @return void
     */
    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }
}
