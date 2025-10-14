<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Models\BillOfLading;
use App\Models\Car;
use App\Models\Container;
use App\Models\Driver;
use App\Models\Job;
use App\Models\TransitCompany;
use App\Models\Vehicle;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;


class BillOfLadingImport implements ToCollection, WithStartRow, WithMapping
{

    public $billOfLadingList=[];
    public function __construct(public $data, private $locationSourceID = null, private $locationDestinationID = null, private $userId = null, private string $type = '')
    {

    }


    public function map($row): array
    {
        try {
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
        }catch (Exception $exception){
            logger()->error($exception->getMessage());
            return [];
        }

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
        try {
            DB::beginTransaction();
            $this->data['total'] = $collection->count();
            $this->sendEvent();
            if ($collection->count() == 0) {
                throw new Exception('No Bill Of Lading Found');
            }
            $collection->each(function ($row) {

                try {
                    /** @var BillOfLading $billOfLading */
                    $billOfLading = BillOfLading::query()->where('code', $row['freight_number'])->first();
                    if (!empty($row['VIN'])) {
                        $this->checkingData($row);
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

//                        $chr = str_replace([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], null, $row['number_plates']);
//                        $one = substr($row['number_plates'], 0, 2);
//                        $second = substr(preg_replace('/\D/', '', $row['number_plates']), 2, 3);
//                        $plate = [
//                            "char" => $chr,
//                            "number1" => $one,
//                            "number2" => $second,
//                            "number3" => $row['plate_area']
//                        ];
                        $plate = [
                            "char" => $row['char_plate'],
                            "number1" => $row['3_digit_plate'],
                            "number2" => $row['2_digit_plate'],
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
                        ]);

                        if (!empty($row['VIN'])) {

                            DB::table('bill_of_lading_item')->insert([
                                'model_id' => $row['VIN'],
                                'model_type' => Vehicle::class,
                                'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                                'type' => $this->type
                            ]);

                            if ($billOfLading->getAttribute('type') == 'bodies'){
                                $this->addVin($billOfLading,$row['VIN']);
                            }

                        }
                        if (!empty($row['container_number'])) {
                            DB::table('bill_of_lading_item')->insert([
                                'model_id' => $container->getAttribute('id'),
                                'model_type' => Container::class,
                                'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                                'type' => $this->type
                            ]);
                        }



                        $this->data['success']++;

                        $row['result'] = 'imported';
                    }
                    else {
                        if (!empty($row['VIN'])) {

                            $itemType = 'vehicles';
                            if ($billOfLading->getAttribute('type') == 'bodies'){
                                $this->addVin($billOfLading,$row['VIN']);
                                $itemType = 'bodies';
                            }
                            DB::table('bill_of_lading_item')->insert([
                                'model_id' => $row['VIN'],
                                'model_type' => Vehicle::class,
                                'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                                'type' => $itemType
                            ]);

                        }
                        if (!empty($row['container_number'])) {
                            DB::table('bill_of_lading_item')->insert([
                                'model_id' => $container->getAttribute('id'),
                                'model_type' => Container::class,
                                'bill_of_lading_id' => $billOfLading->getAttribute('id'),
                                'type' => $this->type
                            ]);
                        }
                    }


                } catch (Exception $exception) {
                    $this->data['error']++;
                    $row['result'] = $exception->getMessage();
                }


                $this->data['done']++;
                $this->sendEvent();


            });

            foreach ($this->billOfLadingList as $row) {
                $row['billOfLading']->createAutoGatePermit($row['vin'], true);
            }

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
            DB::commit();
        }catch (Exception $exception){
            DB::rollBack();
            logger($exception->getMessage());
        }


    }

    /**
     * @throws Exception
     */
    public function checkingData($row): void
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

    private function addVin(BillOfLading $billOfLading,string $vin): void
    {
        if (!isset($this->billOfLadingList[$billOfLading->getAttribute('id')])) {
            $this->billOfLadingList[$billOfLading->getAttribute('id')] = [
                'vin' => [],
                'billOfLading' => $billOfLading
            ];
        }
        $this->billOfLadingList[$billOfLading->getAttribute('id')]['vin'] [] = $vin;

    }
}
