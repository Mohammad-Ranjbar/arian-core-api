<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackingReportExcelRequest;
use App\Http\Requests\TrackingRequest;
use App\Http\Requests\TrackingSaleLocationExcelRequest;
use App\Http\Resources\TrackingResource;
use App\Http\Resources\VehicleResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Models\SendErp;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Services\TrackingService\TrackingServiceFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class ProductionTrackingController extends Controller
{

    /**
     * @param TrackingRequest $request
     * @param Vehicle $vehicle
     * @return JsonResponse|VehicleResource
     * @throws AuthorizationException
     */
    public function submit(TrackingRequest $request, Vehicle $vehicle): JsonResponse|VehicleResource
    {
        $validatedData = $request->validated();
        if ($validatedData['type'] === 'enter' && $validatedData['model_type']) {
            $this->authorize('production-tracking.' . $validatedData['model_type'] . '.enter');
        }
        if ($validatedData['type'] === 'exit' && $validatedData['model_type']) {
            $this->authorize('production-tracking.' . $validatedData['model_type'] . '.exit');
        }

        $modelTrackingService = TrackingServiceFactory::getInstance($validatedData['model_type']);
        $data = $modelTrackingService->{$validatedData['type']}($validatedData);
        if (!$data) {
            return $this->responseError('cant not exit or enter to this location', 400);
        }
        $record = $vehicle->tracking()->create($data);

        if (method_exists($modelTrackingService, 'sendToErp')) {
            if (
                ($record->getAttribute('type') === 'enter' && $record->getAttribute('model')->sendErp?->on_enter)
                || ($record->getAttribute('type') === 'exit' && $record->getAttribute('model')->sendErp?->on_exit)
            ) {
                $modelTrackingService->sendToErp($record);
            }
        }


        return new VehicleResource($vehicle);
    }

    /**
     * @param Tracking $tracking
     * @return TrackingResource
     */
    public function update(Tracking $tracking): TrackingResource
    {
        $tracking->update(['data' => null, 'user_id' => auth()->id()]);

        $modelTrackingService = TrackingServiceFactory::getInstance($tracking->getAttribute('model')->getTable());
        $type = $tracking->getAttribute('type') === 'enter' ? 'on_enter' : 'on_exit';

        if (method_exists($modelTrackingService, 'sendToErp') &&
            SendErp::query()->where('model_type', $tracking->getAttribute('model_type'))
                ->where($type, true)
                ->where('model_id', $tracking->getAttribute('model_id'))
                ->exists()) {
            $modelTrackingService->sendToErp($tracking);
        }


        return new TrackingResource($tracking);
    }


    public function reportExcel(TrackingReportExcelRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-tracking';
        $validatedData['user_id'] = $request->validated('user_id');
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
        $validatedData['user_id'] = $request->validated('user_id');
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
