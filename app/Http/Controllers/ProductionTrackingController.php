<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingExitDoorReportExcelRequest;
use App\Http\Requests\TrackingReportExcelRequest;
use App\Http\Requests\TrackingRequest;
use App\Http\Requests\TrackingSaleLocationExcelRequest;
use App\Http\Resources\TrackingResource;
use App\Http\Resources\VehicleResource;
use App\Jobs\ExportJob;
use App\Models\ErpJob;
use App\Models\Job;
use App\Models\ProductionLine;
use App\Models\RestErpJob;
use App\Models\SendErp;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\TrackingService\TrackingServiceFactory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProductionTrackingController extends Controller
{


    /**
     * @throws Throwable
     */
    public function submit(TrackingRequest $request, Vehicle $vehicle): JsonResponse|VehicleResource
    {
        $validatedData = $request->validated();
        try {
            if ($validatedData['type'] == 'pbs' && $validatedData['model_type'] == 'production_lines') {
                $condition = Tracking::query()
                    ->where('model_type', ProductionLine::class)
                    ->where('type', 'pbs')
                    ->where('vin', $validatedData['vin'])
                    ->where('model_id', $validatedData['model_id'])
                    ->exists();
                if ($condition) {
                    return $this->responseError('شماره وارد شده قبلا به PBS وارد شده است');
                }

            }
            if ($validatedData['type'] == 'enter' && $validatedData['model_type'] == 'production_lines') {
                $condition = !Tracking::query()
                    ->where('model_type', ProductionLine::class)
                    ->where('type', 'pbs')
                    ->where('vin', $validatedData['vin'])
                    ->where('model_id', $validatedData['model_id'])
                    ->exists();
                if ($condition) {
                    return $this->responseError('ابتدا باید ورود به PBS ثبت شود');
                }


                $condition = Tracking::query()
                    ->where('model_type', ProductionLine::class)
                    ->where('type', 'enter')
                    ->where('vin', $validatedData['vin'])
                    ->where('model_id', $validatedData['model_id'])
                    ->exists();
                if ($condition) {
                    return $this->responseError('شماره وارد شده قبلا به این خط تولید وارد شده است');
                }

            }
            if ($validatedData['type'] == 'exit' && !Tracking::query()->where('vin', $vehicle->vin)->where('type', 'enter')->exists()) {
                return $this->responseError('لطفا ابتدا ثبت ورود را انجام دهید');
            }


            Tracking::query()->where('vin', $vehicle->vin)->where('type', 'enter')->exists();

            DB::beginTransaction();

            if ($validatedData['type'] == 'enter') {
                $vehicle->update([
                    'grade' => $validatedData['grade']
                ]);
            }

            if ($validatedData['type'] === 'enter' && $validatedData['model_type']) {
                $this->authorize('production-tracking.' . $validatedData['model_type'] . '.enter');
            }
            if ($validatedData['type'] === 'exit' && $validatedData['model_type']) {
                $this->authorize('production-tracking.' . $validatedData['model_type'] . '.exit');
            }

            $modelTrackingService = TrackingServiceFactory::getInstance($validatedData['model_type']);

            $data = $modelTrackingService->{$validatedData['type']}($validatedData);
            if (!$data) {
                DB::rollBack();
                return $this->responseError('cant not exit or enter to this location', 400);
            }
            $record = $vehicle->tracking()->create($data);
            $erpSubmission = null;
            if (!$validatedData['type'] !== 'pbs' &&method_exists($modelTrackingService, 'sendToErp')) {
                if ($record->getAttribute('type') === 'exit' && $record->getAttribute('model')->sendErp?->on_exit) {
                    $erpSubmission = $modelTrackingService->sendToErp($record);
                    // This is local validation/queue acceptance; the ERP result arrives in the job.
                    if (!$erpSubmission['success']) {
                        DB::rollBack();
                        return response()->json(['error' => $erpSubmission['error']], $erpSubmission['http_status']);
                    }
                }
            }
            DB::commit();
            $resource = new VehicleResource($vehicle);
            if ($erpSubmission !== null) {
                return $resource->additional($erpSubmission['data'])->response()->setStatusCode(202);
            }
            return $resource;
        } catch (Exception $exception) {
            DB::rollBack();

            return $this->responseError($exception->getMessage(), 400);
        }

    }


    public function update(Tracking $tracking): TrackingResource|JsonResponse
    {
        $check = ErpJob::query()->where('model_type', Tracking::class)->where('model_id', $tracking->getAttribute('id'))->where('status', '!=', 'failed')->exists();
        $restCheck = RestErpJob::query()->where('model_type', $tracking->getMorphClass())
            ->where('model_id', $tracking->getKey())->where('endpoint', 'ProductionPostingBINs')
            ->whereIn('status', ['pending', 'in-progress', 'uncertain', 'success'])->exists();
        if ($check || $restCheck) {
            return $this->responseError('کاربر دیگری در جاب دیگر در حال بررسی این مورد است');
        }
        $modelTrackingService = TrackingServiceFactory::getInstance($tracking->getAttribute('model')->getTable());
        $type = $tracking->getAttribute('type') === 'enter' ? 'on_enter' : 'on_exit';

        $erpSubmission = null;
        if (method_exists($modelTrackingService, 'sendToErp') &&
            SendErp::query()->where('model_type', $tracking->getAttribute('model_type'))
                ->where($type, true)
                ->where('model_id', $tracking->getAttribute('model_id'))
                ->exists()) {
            DB::beginTransaction();
            $tracking->update(['user_id' => auth()->id()]);
            try {
                $erpSubmission = $modelTrackingService->sendToErp($tracking);
                if (!$erpSubmission['success']) {
                    DB::rollBack();
                    return response()->json(['error' => $erpSubmission['error']], $erpSubmission['http_status']);
                }
                DB::commit();
            } catch (Exception $exception) {
                DB::rollBack();
                return $this->responseError($exception->getMessage(), 400);
            }

        }


        $tracking->data = $tracking->fresh()->data;
        $resource = new TrackingResource($tracking);
        if ($erpSubmission !== null) {
            return $resource->additional($erpSubmission['data'])->response()->setStatusCode(202);
        }
        return $resource;
    }

    public function destroy(Tracking $tracking): JsonResponse
    {
        try {
            $tracking->delete();

            return response()->json([
                'message' => 'با موفقیت حذف گردید'
            ]);
        } catch (Exception $exception) {

            return $this->responseError($exception->getMessage(), 400);
        }

    }


    public function reportExcel(TrackingReportExcelRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-tracking';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    public function reportExcelExitDoor(TrackingExitDoorReportExcelRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-tracking-exit-door';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $request->validated();
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }


    public function reportExcelSaleLocation(TrackingSaleLocationExcelRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $jobData = $validatedData;
        $jobData['model_type'] = 'sale_locations';
        $validatedData['type'] = 'export-tracking-sale-location';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = $jobData;
        $job = Job::query()->create($validatedData);

        ExportJob::dispatch($job->getAttribute('uuid'));

        return response()->json([
            'message' => 'export job created',
            'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }
}
