<?php

namespace App\Http\Controllers;

use App\Filters\SynchronousProductionLineIdFilter;
use App\Filters\SynchronousTitleFilter;
use App\Http\Requests\UpdateCrossReferenceRequest;
use App\Http\Resources\SynchronousResource;
use App\Jobs\ExportJob;
use App\Models\Job;
use App\Models\NavColor;
use App\Models\Synchronous;
use App\Models\SynchronousItem;
use App\Services\Erp\ErpCrossReference;
use App\Services\Erp\RestApi\CreateSynchroensService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Pipeline;

class SynchronousController extends Controller
{

    public function __construct(
        private readonly ErpCrossReference $erpCrossReference,
        private readonly CreateSynchroensService $createSynchroens
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function otherIndex(): AnonymousResourceCollection
    {
        $this->authorize('index.other-synchronous');
        $data = Pipeline::send(
            Synchronous::query()->with('synchronousType')
                ->whereHas('synchronousType', function ($q) {
                    $q->where('type', 'other');
                })
                ->latest()
        )->through([
            SynchronousProductionLineIdFilter::class,
            SynchronousTitleFilter::class
        ])->thenReturn()->paginate(request()->per_page ?? 15);
        return SynchronousResource::collection($data);
    }

    /**
     * @throws AuthorizationException
     */
    public function engineIndex(): AnonymousResourceCollection
    {
        $this->authorize('index.engine-synchronous');
        $data = Pipeline::send(
            Synchronous::query()->with('synchronousType')
                ->whereHas('synchronousType', function ($q) {
                    $q->where('type', 'engine');
                })
                ->latest()
        )->through([
            SynchronousProductionLineIdFilter::class,
            SynchronousTitleFilter::class
        ])->thenReturn()->paginate(request()->per_page ?? 15);
        return SynchronousResource::collection($data);
    }

    public function reportExcel(): JsonResponse
    {
        $validatedData = request()->all();
        $validatedData['type'] = 'export-synchronous-engine';
        $validatedData['user_id'] = Auth::id();
        $validatedData['data'] = request()->all();
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

    public function closeSynchronous(Synchronous $synchronous): SynchronousResource
    {

        $synchronous->update([
            'capacity' => 0
        ]);

        return new SynchronousResource($synchronous);
    }

    public function printSynchronous(Synchronous $synchronous): SynchronousResource
    {

        return new SynchronousResource($synchronous);
    }

    public function getBySynchronousNumber(string $number): JsonResponse|SynchronousResource
    {
        $items = explode('-', $number);
        if (count($items) !== 3) {
            return response()->json([
                'message' => 'شماره وارد شده نامعتبر است'
            ], 422);
        }
        $synchronous = Synchronous::query()
            ->whereDoesntHave('synchronousType', function ($q) {
                $q->where('type', 'engine');
            })
            ->where('synchronous_type_id', $items[1])
            ->where('capacity', 0)
            ->where('counter', $items[2])
            ->first();

        if (!$synchronous) {
            return response()->json([
                'message' => 'شماره وارد شده نامعتبر است'
            ], 422);
        }
        return new SynchronousResource($synchronous);
    }

    public function updateCrossReference(
        UpdateCrossReferenceRequest $request,
        SynchronousItem $synchronousItem
    ): JsonResponse|bool {
        $validatedData = $request->validated();

        if ($synchronousItem->getAttribute('checked')) {
            abort('422', 'سنکرون قبلا ارسال شده است');
        }

        $reference = $synchronousItem->erpProductItem->reference;

        $data = $this->erpCrossReference->get($reference);

        if (!in_array($validatedData['cross_reference'], $data)) {
            return response()->json([
                'message' => 'کراس رفرنس وارد شده نامعتبر است'
            ], 422);
        }
        $synchronousItem->update([
            'cross_reference' => $validatedData['cross_reference'],
            'checked'         => true
        ]);

        $count = SynchronousItem::query()
            ->where('synchronous_id', $synchronousItem->getAttribute('synchronous_id'))
            ->where('checked', false)->count();
        if ($count == 0) {
            $items = SynchronousItem::query()
                ->where('synchronous_id', $synchronousItem->getAttribute('synchronous_id'))->get();
            $crossReferences = $items->pluck('cross_reference')->implode(',');
            $docP = $synchronousItem->synchronous->number;
            $locationCodeP = $synchronousItem->synchronous->synchronousType->productionLine->central_location;
            $newLocationCodeP = $synchronousItem->synchronous->synchronousType->productionLine->location;

            $variantCodeP = $items->map(function ($item) {

                return !empty($item->vehicle->data['ColorCode']) ? $item->vehicle->color_code : NavColor::query()
                    ->where('car_model_id',$item->vehicle->car_model_id)
                    ->where('color',$item->vehicle->color_code)
                    ->first()?->getAttribute('code');


//               return NavColor::query()->where('color',$item->vehicle->color_code)->first()?->getAttribute('code') ?? '';

            })->implode(',');


            $numberP = $items->count();
            $batchNoP = Carbon::now()->toDateTimeString();

            $result = $this->createSynchroens->dispatch(
                $crossReferences, $docP, $locationCodeP, $newLocationCodeP,
                $variantCodeP, $numberP, $batchNoP,
                model: $synchronousItem, userId: Auth::id()
            );

            if (!$result['success']) {
                return response()->json([
                    'error' => $result['error'],
                    'rest_erp_job_id' => $result['rest_erp_job_id'] ?? null,
                ], $result['http_status']);
            }

            return response()->json($result['data'], $result['http_status'] === 202 ? 202 : 200);
        }

        return true;
    }
}
