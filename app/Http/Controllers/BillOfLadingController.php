<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Http\Requests\BillOfLadingExcelRequest;
use App\Http\Requests\ImportBillOfLadingRequest;
use App\Http\Requests\ReturnContainerRequest;
use App\Http\Requests\SetPriorityRequest;
use App\Http\Resources\BillOfLadingResource;
use App\Jobs\ExportJob;
use App\Jobs\ImportJob;
use App\Models\ActionLog;
use App\Models\BillOfLading;
use App\Models\GatePermit;
use App\Models\Job;
use App\Models\Vehicle;
use App\Repositories\ActionLogRepository;
use App\Repositories\BillOfLadingRepository;
use App\Services\SmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillOfLadingController extends CrudController
{
    /**
     * @var bool
     */
    protected bool $appendUserId = true;
    protected array $appendStoreData = ['status' => 'new'];


    public function __construct(
        public readonly BillOfLadingRepository $repository,
        private readonly SmsService            $smsService,
        private readonly ActionLogRepository   $actionLogRepository,
    )
    {

        $this->applyAuthorizeResource();
        $this->setResource(BillOfLadingResource::class);
    }


    public function destroy($id): JsonResponse
    {
        $model = $this->repository->show($id);
        if ($model->status != 'new') {
            return $this->responseError('Can not delete this item', 400);
        }

        if ($model->type === 'containers') {
            $model->containers()->update(['on_shipping' => false]);
            $model->containers()->detach();
        }
        if ($model->type === 'vehicles' || $model->type === 'bodies') {
            $model->vehicles()->detach();
        }

        $model->actionLogs()->delete();
        $model->forceDelete();

        return response()->json([
            'message' => 'حذف با موفقیت انجام گردید'
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function changeStatus(
        BillOfLading $billOfLading,
        string       $status
    ): BillOfLadingResource|JsonResponse
    {
        $this->authorize('bill-of-lading.change-status.' . $status);

        if (!$this->checkCanChangeStatus($billOfLading, $status)) {
            return $this->responseError('The selected status is available in registered statuses');
        }
        try {
            DB::beginTransaction();
            $this->repository->update($billOfLading->getAttribute('id'), compact('status'));
            $this->actionLog($billOfLading, $status);
            if ($status === 'discharge') {
                $billOfLading->containers()->update(['on_shipping' => false]);
                // todo : update vehicle current location and tracking
            }
            DB::commit();
            return new BillOfLadingResource($billOfLading);
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getMessage(), 500);
        }
    }

    private function checkCanChangeStatus(BillOfLading $billOfLading, $status): bool
    {
        return !$billOfLading->actionLogs()->where('action', $status)->exists();
    }

    private function actionLog(BillOfLading $billOfLading, $status): void
    {
        $billOfLading->actionLogs()->create([
            'user_id' => Auth::id(), 'action' => $status
        ]);

    }

    public function setPriority(BillOfLading $billOfLading, SetPriorityRequest $request): BillOfLadingResource
    {
        $validatedData = $request->validated();
        $billOfLading->update($validatedData);
        return new BillOfLadingResource($billOfLading);
    }

    public function notice(BillOfLading $billOfLading
    ): bool|PendingDispatch|PendingClosureDispatch
    {
        return $this->smsService->send($billOfLading->getAttribute('car')->driver->phone, 'driver-enter-notify');
    }

    public function getTemplateBillOfLadingForVehicle(): BinaryFileResponse
    {
        $headers = BillOfLading::HEAD_VEHICLE;
        $export = new TemplateExport($headers);
        return Excel::download($export, $this->repository->model->getTable() . '-template-for-vehicle' . time() . '.xlsx');
    }

    public function getTemplateBillOfLadingForContainer(): BinaryFileResponse
    {
        $headers = BillOfLading::HEAD_CONTAINER;
        $export = new TemplateExport($headers);
        return Excel::download($export, $this->repository->model->getTable() . '-template-for-container' . time() . '.xlsx');
    }

    public function importBillOfLading(ImportBillOfLadingRequest $request): JsonResponse
    {

        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type_bill_of_lading'] = $validatedData['type'];
        $validatedData['type'] = 'import-' . $this->repository->model->getTable();
        $validatedData['data'] = [
            'file' => $validatedData['file'],
            'location_source_id' => $validatedData['location_source_id'],
            'location_destination_id' => $validatedData['location_destination_id'],
            'type' => $validatedData['type_bill_of_lading'],
        ];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return response()->json([
            'message' => 'import job created', 'data' => [
                'uuid' => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

    public function reportExcel(BillOfLadingExcelRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['type'] = 'export-bill-of-ladings';
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

    public function returnContainer(BillOfLading $billOfLading, ReturnContainerRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        if (!$billOfLading->canReturn()) {
            return response()->json([
                'message' => 'کاتینر هنوز تخلیه نشده است'
            ]);
        }
        try {

            $validatedData['user_id'] = Auth::id();
            $validatedData['model_type'] = BillOfLading::class;
            $validatedData['model_id'] = $billOfLading->getAttribute('id');
            $validatedData['action'] = ActionLog::RETURN_CONTAINER_AT;
            $validatedData['data'] = $validatedData['return_container_at'];
            $this->actionLogRepository->store($validatedData);

            return response()->json([
                'message' => 'return container created'
            ], 201);
        } catch (Exception $exception) {
            return $this->responseError($exception->getMessage(), 500);
        }


    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function store(Request $request)
    {
        if (!isset($this->repository)) {
            return $this->responseError('repository not found', 400);
        }
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(),
            $this->customStoreRules()));
        $request->validate([
            'item_type' => 'required_if:type,bodies|in:0,1,2',
            'code' => [
                'required',
                'string',
                'max:255', // Adjust as needed
                Rule::unique('bill_of_ladings')->where(function ($query) use ($request) {
                    return $query->where('location_source_id', $request->get('location_source_id'))
                        ->where('location_destination_id', $request->get('location_destination_id'));
                }),
            ],
        ]);
        $validatedData = array_merge($validatedData, $this->appendStoreData);

        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            if ($validatedData['type'] == 'bodies') {
                foreach ($validatedData['items'] as $vin) {
                    $body = Vehicle::query()->where('vin', $vin)->first();
                    if (empty($body->getAttribute('qc_status'))) {
                        $message = ' لطفا کد کیفیت '.$body->getAttribute('vin').' وارد شود ';
                        throw new Exception($message);
                    }
                }
            }
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function customStoreRules(): array
    {
        if (request()->get('type') === 'containers') {
            return [
                'code' => [
                    'required',
                    'string',
                    'max:255', // Adjust as needed
                    Rule::unique('bill_of_ladings')->where(function ($query) {
                        return $query->where('location_source_id', request()->get('location_source_id'))
                            ->where('location_destination_id', request()->get('location_destination_id'));
                    }),
                ],
                'items.*' => Rule::exists('containers', 'id')->where('on_shipping', false),
            ];
        }
        if (request()->get('type') === 'vehicles' || request()->get('type') === 'bodies') {
            return [
//                'item_type' => 'required_if:type,bodies|in:0,1,2',
                'code' => [
//                    'item_type' => 'required_if:type,bodies|in:0,1,2',
                    'required',
                    'string',
                    'max:255', // Adjust as needed
                    Rule::unique('bill_of_ladings')->where(function ($query) {
                        return $query->where('location_source_id', request()->get('location_source_id'))
                            ->where('location_destination_id', request()->get('location_destination_id'));
                    }),
                ],
                'items.*' => Rule::exists('vehicles', 'vin'),
            ];
        }

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bill_of_ladings')->where(function ($query) {
                    return $query->where('location_source_id', request()->get('location_source_id'))
                        ->where('location_destination_id', request()->get('location_destination_id'));
                }),
            ],
        ];

    }

    /**
     * @throws Exception
     */
    public function afterSave($record, $data)
    {
        if ($data['type'] === 'containers') {
            $record->containers()->syncWithPivotValues($data['items'], ['type' => 'containers']);
            $record->containers()->update(['on_shipping' => true]);
        }
        if ($data['type'] === 'vehicles') {
            foreach ($data['items'] as $vin) {
                if (!GatePermit::query()
                    ->where('model_type', Vehicle::class)
                    ->where('model_id', $vin)->exists()) {
                    throw new Exception('Please add gate permit for vin ' . $vin . ' create bill of ladings');
                }
            }
            $record->vehicles()->syncWithPivotValues($data['items'], ['type' => 'vehicles']);
        }

        if ($data['type'] == 'bodies') {
            $record->createAutoGatePermit($data['items'],$data['item_type'],true);
            $record->vehicles()->syncWithPivotValues($data['items'], ['type' => 'bodies']);
        }


        $action = $record->wasRecentlyCreated ? 'new' : 'edit';

        $record->update([
            'created_at' => Carbon::now()
        ]);

        $record->actionLogs()->create(['user_id' => Auth::id(), 'action' => $action]);

        return $record;
    }
}
