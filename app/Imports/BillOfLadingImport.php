<?php

namespace App\Imports;

use App\Events\JobProcessEvent;
use App\Exports\ResultExport;
use App\Http\Controllers\VehicleController;
use App\Models\BillOfLading;
use App\Models\Car;
use App\Models\Container;
use App\Models\Driver;
use App\Models\Job;
use App\Models\TransitCompany;
use App\Models\Vehicle;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class BillOfLadingImport implements ToCollection, WithMapping, WithStartRow
{
    public $billOfLadingList = [];

    private array $vehicleLookupErrors = [];

    public function __construct(
        public $data,
        private $locationSourceID = null,
        private $locationDestinationID = null,
        private $userId = null,
        private string $type = '',
        private string $item_type = ''
    ) {}

    public function map($row): array
    {
        try {
            $columns = [];

            $job = Job::query()->find($this->data['id']);
            $this->userId = $job->getAttribute('user_id');
            $data = $job->getAttribute('data');

            $this->type = $data['type'];
            $this->item_type = $data['item_type'];
            $this->locationSourceID = $data['location_source_id'];
            $this->locationDestinationID = $data['location_destination_id'];
            $headers = match ($data['type']) {
                'containers' => BillOfLading::HEAD_CONTAINER,
                'vehicles', 'bodies' => BillOfLading::HEAD_VEHICLE,
                default => [],
            };
            foreach ($headers as $key => $value) {
                $columns[$value] = $row[$key];
            }

            return $columns;
        } catch (Exception $exception) {
            logger()->error($exception->getMessage());

            return [];
        }

    }

    public function startRow(): int
    {
        return 2;
    }

    /**
     * @throws Throwable
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
                $this->importRow($row);
            });
            $this->createAutoGatePermits();
            $this->storeResult($collection);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            logger()->error($exception->getMessage());
        }

    }

    private function importRow($row): void
    {
        try {
            $billOfLading = BillOfLading::query()
                ->where('code', $row['freight_number'])
                ->where('batch', $row['batch'])->first();

            if (! empty($row['VIN'])) {
                $this->checkingData($row);
                $this->ensureVehicleExists($row['VIN']);
            }

            $container = null;
            if (! empty($row['container_number'])) {
                $this->checkingData($row);
                $container = Container::query()->where('code', $row['container_number'])->firstOrCreate([
                    'code' => $row['container_number'],
                ]);
            }

            $isNew = ! $billOfLading;
            if ($isNew) {
                $billOfLading = $this->createBillOfLading($row);
            }

            $this->insertItems($billOfLading, $row, $container);

            if ($isNew) {
                $this->data['success']++;
                $row['result'] = 'imported';
            }
        } catch (Exception $exception) {
            $this->data['error']++;
            $row['result'] = $exception->getMessage();
        }

        $this->data['done']++;
        $this->sendEvent();
    }

    private function ensureVehicleExists(string $vin): void
    {
        if (Vehicle::withTrashed()->whereKey($vin)->exists()) {
            return;
        }

        if (isset($this->vehicleLookupErrors[$vin])) {
            throw new Exception($this->vehicleLookupErrors[$vin]);
        }

        try {
            $response = app(VehicleController::class)->getByVin($vin);

            // getByVin may return 403 after saving because of its display permission check.
            if (! Vehicle::withTrashed()->whereKey($vin)->exists()) {
                $message = $response instanceof JsonResponse
                    ? ($response->getData(true)['message'] ?? 'Vehicle not found after ERP lookup: '.$vin)
                    : 'Vehicle not found after ERP lookup: '.$vin;

                throw new Exception($message);
            }
        } catch (Exception $exception) {
            $this->vehicleLookupErrors[$vin] = $exception->getMessage();

            throw $exception;
        }
    }

    /**
     * @throws Exception
     */
    private function createBillOfLading($row): BillOfLading
    {
        if (! $driver = Driver::query()->where('national_code', $row['driver_national_code'])->first()) {
            $driver = Driver::query()->create([
                'national_code' => $row['driver_national_code'],
                'phone' => $row['driver_mobile'],
                'name' => $row['driver_name'],
            ]);
        }
        if (! $transitCompany = TransitCompany::query()->where('title', $row['shipping_company'])->first()) {
            $transitCompany = TransitCompany::query()->create([
                'title' => $row['shipping_company'],
            ]);
        }

        if (! in_array($row['char_plate'], [
            'ب', 'ج', 'س', 'ص', 'ط', 'ق', 'ل', 'م', 'ن', 'و', 'ه', 'ی',
            'الف', 'پ', 'ت', 'ث', 'ز', 'ژ', 'ش', 'ع', 'ف', 'ک', 'گ', 'D', 'S',
        ])) {
            throw new Exception('حرف پلاک نامجاز است');
        }

        $plate = [
            'char' => $row['char_plate'],
            'number1' => $row['2_digit_plate'],
            'number2' => $row['3_digit_plate'],
            'number3' => $row['plate_area'],
        ];

        if (! $car = Car::query()->where('plate', $plate)->first()) {
            $car = Car::query()->create([
                'plate' => json_encode($plate),
                'car' => $row['vehicle_title'],
                'transit_company_id' => $transitCompany->getAttribute('id'),
                'driver_id' => $driver->getAttribute('id'),
            ]);
        }

        return BillOfLading::query()->create([
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
    }

    private function insertItems(BillOfLading $billOfLading, $row, ?Container $container): void
    {
        if (! empty($row['VIN'])) {
            $this->insertItem($billOfLading, $row['VIN'], Vehicle::class, $this->type);

            if (in_array($this->type, ['bodies', 'vehicles'], true)) {
                $this->addVin($billOfLading, $row['VIN']);
            }
        }

        if (! empty($row['container_number'])) {
            $this->insertItem($billOfLading, $container->getAttribute('id'), Container::class, $this->type);
        }
    }

    private function insertItem(BillOfLading $billOfLading, $modelId, string $modelType, string $type): void
    {
        DB::table('bill_of_lading_item')->insert([
            'model_id' => $modelId,
            'model_type' => $modelType,
            'bill_of_lading_id' => $billOfLading->getAttribute('id'),
            'type' => $type,
        ]);
    }

    private function createAutoGatePermits(): void
    {
        if ($this->item_type !== '') {
            foreach ($this->billOfLadingList as $row) {
                $row['billOfLading']->createAutoGatePermit($row['vin'], $this->item_type, true);
            }
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function storeResult(Collection $collection): void
    {
        $uuid = Str::uuid();
        if ($this->type === 'vehicles' || $this->type === 'bodies') {
            $headers = BillOfLading::HEAD_VEHICLE;
        } else {
            $headers = BillOfLading::HEAD_CONTAINER;
        }
        Excel::store(new ResultExport($collection->prepend($headers)), 'public/results/'.$uuid.'.xlsx');
        Job::query()->find($this->data['id'])->update([
            'result' => [
                'file' => route('dl', ['path' => 'results/'.$uuid.'.xlsx']),
                'total' => $this->data['total'],
                'success' => $this->data['success'],
                'error' => $this->data['error'],
            ],
        ]);
    }

    public function sendEvent(): void
    {
        event(new JobProcessEvent($this->data));
    }

    /**
     * @throws Exception
     */
    public function checkingData($row): void
    {
        foreach ([
            'vehicle_title',
            'plate_area',
            'driver_name',
            'driver_mobile',
            'driver_national_code',
            'freight_number',
            'request_number',
            'cottage_number',
            'weight',
            'batch',
        ] as $field) {
            if (empty($row[$field])) {
                throw new Exception($field.' is empty');
            }
        }
    }

    private function addVin(BillOfLading $billOfLading, string $vin): void
    {
        if (! isset($this->billOfLadingList[$billOfLading->getAttribute('id')])) {
            $this->billOfLadingList[$billOfLading->getAttribute('id')] = [
                'vin' => [],
                'billOfLading' => $billOfLading,
            ];
        }
        $this->billOfLadingList[$billOfLading->getAttribute('id')]['vin'][] = $vin;

    }
}
