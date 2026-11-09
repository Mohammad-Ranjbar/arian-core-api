<?php

namespace App\Services\Erp;

use App\Http\Controllers\VehicleController;
use App\Models\Vehicle;
use App\Models\VehicleAllocation;
use Exception;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VehicleAllocationService
{
    public function __construct(private readonly ErpOdataClient $client) {}

    public function sync(): array
    {
        $records = $this->fetchRecords();

        Validator::make(['records' => $records], [
            'records.*' => ['required', 'array'],
            'records.*.ProductionOrderNo' => ['required', 'string', 'max:255'],
            'records.*.DueDate' => ['required', 'date_format:Y-m-d'],
            'records.*.VINNo' => ['required', 'string', 'max:255', 'distinct:strict'],
            'records.*.VariantCode' => ['required', 'string', 'max:255'],
        ], [
            'required' => 'فیلد :attribute در اطلاعات تخصیص خودرو الزامی است.',
            'array' => 'ساختار :attribute در اطلاعات تخصیص خودرو معتبر نیست.',
            'string' => 'فیلد :attribute باید از نوع رشته باشد.',
            'max' => 'طول فیلد :attribute نباید بیشتر از :max نویسه باشد.',
            'date_format' => 'فیلد :attribute باید تاریخ معتبر با قالب :format باشد.',
            'distinct' => 'برای هر VIN فقط یک تخصیص مجاز است؛ مقدار :attribute تکراری است.',
        ])->validate();

        $created = 0;
        $updated = 0;
        $skippedVins = [];
        foreach ($records as $record) {
            try {
                $allocation = $this->syncRecord($record);
            } catch (Exception $exception) {
                // getData wraps its original ValidationException in an Exception.
                $cause = $exception;
                while (! $cause instanceof ValidationException && $cause->getPrevious() !== null) {
                    $cause = $cause->getPrevious();
                }
                if (! $cause instanceof ValidationException) {
                    throw $exception;
                }

                $skippedVins[] = $record['VINNo'];
                Log::warning('تخصیص خودرو به دلیل پیدا نشدن خودروی قابل استفاده رد شد.', [
                    'vin' => $record['VINNo'],
                    'reason' => $cause->getMessage(),
                ]);

                continue;
            }

            if ($allocation->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        // Compare against every returned VIN, including records skipped locally.
        $completed = VehicleAllocation::query()
            ->where('is_complete', false)
            ->whereNotIn('vin', array_column($records, 'VINNo'))
            ->update(['is_complete' => true]);

        return [
            'total' => count($records),
            'created' => $created,
            'updated' => $updated,
            'skipped' => count($skippedVins),
            'skipped_vins' => $skippedVins,
            'completed' => $completed,
        ];
    }

    private function syncRecord(array $record): VehicleAllocation
    {
        return DB::transaction(function () use ($record) {
            $vin = $record['VINNo'];
            $vehicle = Vehicle::withTrashed()->withoutEagerLoads()->lockForUpdate()->find($vin);

            if ($vehicle?->trashed()) {
                throw ValidationException::withMessages(['vin' => 'خودرو با VIN '.$vin.' حذف شده است.']);
            }

            if ($vehicle === null) {
                app(VehicleController::class)->getData($vin);
                $vehicle = Vehicle::query()->withoutEagerLoads()->lockForUpdate()->find($vin);

                if ($vehicle === null) {
                    throw ValidationException::withMessages(['vin' => 'خودرو با VIN '.$vin.' پس از دریافت از ERP ثبت نشده است.']);
                }
            }

            return VehicleAllocation::query()->updateOrCreate(['vin' => $vin], [
                'production_order_number' => $record['ProductionOrderNo'],
                'due_date' => $record['DueDate'],
                'variant_code' => $record['VariantCode'],
                'car_model_id' => $vehicle->car_model_id,
                'make_code_id' => $vehicle->make_code_id,
                'data' => $record,
            ]);
        });
    }

    private function fetchRecords(): array
    {
        $url = config('erp.vehicle_allocation');
        if (! is_string($url) || trim($url) === '') {
            throw new HttpException(500, 'آدرس OData تخصیص خودرو در erp.vehicle_allocation تنظیم نشده است.');
        }

        $baseUri = new Uri($url);
        $visited = [];
        $records = [];

        do {
            if (isset($visited[$url])) {
                throw new HttpException(502, 'پیوند صفحه‌بندی OData تخصیص خودرو تکراری است.');
            }
            $visited[$url] = true;
            $response = $this->client->get($url);

            if (! is_object($response) || ! isset($response->value) || ! is_array($response->value)) {
                throw new HttpException(502, 'دریافت اطلاعات تخصیص خودرو از ERP ناموفق بود یا پاسخ OData معتبر نیست.');
            }

            foreach ($response->value as $record) {
                $records[] = is_object($record) ? (array) $record : $record;
            }

            $nextLink = $response->{'@odata.nextLink'} ?? null;
            if ($nextLink === null || $nextLink === '') {
                break;
            }
            if (! is_string($nextLink)) {
                throw new HttpException(502, 'پیوند صفحه‌بندی OData تخصیص خودرو معتبر نیست.');
            }

            $nextUri = UriResolver::resolve(new Uri($url), new Uri($nextLink));
            if ($nextUri->getScheme() !== $baseUri->getScheme() || $nextUri->getAuthority() !== $baseUri->getAuthority()) {
                throw new HttpException(502, 'پیوند صفحه‌بندی OData تخصیص خودرو باید متعلق به همان سرور ERP باشد.');
            }
            $url = (string) $nextUri;
        } while (true);

        return $records;
    }
}
