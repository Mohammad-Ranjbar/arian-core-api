<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Http\Requests\ImportBillOfLadingRequest;
use App\Http\Requests\SetPriorityRequest;
use App\Http\Resources\BillOfLadingResource;
use App\Jobs\ImportJob;
use App\Models\BillOfLading;
use App\Models\Job;
use App\Repositories\BillOfLadingRepository;
use App\Services\SmsService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Bus\PendingClosureDispatch;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillOfLadingController extends CrudController
{
    /**
     * @var bool
     */
    protected bool  $appendUserId    = true;
    protected array $appendStoreData = ['status' => 'new'];


    public function __construct(
        public readonly BillOfLadingRepository $repository,
        private readonly SmsService $smsService
    ) {

        $this->applyAuthorizeResource();
        $this->setResource(BillOfLadingResource::class);
    }


    /**
     * @throws Exception
     */
    public function afterSave($record, $data)
    {
        if ($data['type'] === 'containers') {
            $record->containers()->sync($data['items']);
            $record->containers()->update(['on_shipping' => true]);
        }
        if ($data['type'] === 'vehicles' || $data['type'] === 'bodies') {
            $record->vehicles()->sync($data['items']);
        }

        $action = $record->wasRecentlyCreated ? 'new' : 'edit';
        $record->actionLogs()->create(['user_id' => Auth::id(), 'action' => $action]);

        return $record;
    }

    public function customStoreRules(): array
    {
        if (request()->get('type') === 'containers') {
            return [
                'items.*' => Rule::exists('containers', 'id')->where('on_shipping', false),
            ];
        }
        if (request()->get('type') === 'vehicles' || request()->get('type') === 'bodies') {
            return [
                'items.*' => Rule::exists('vehicles', 'vin'),
            ];
        }

        return [];

    }

    public function destroy($id)
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
        return $model->forceDelete();
    }

    /**
     * @throws AuthorizationException
     */
    public function changeStatus(
        BillOfLading $billOfLading,
        string $status
    ): BillOfLadingResource|JsonResponse {
        $this->authorize('bill-of-lading.change-status.'.$status);

        if (!$this->checkCanChangeStatus($billOfLading, $status)) {
            return $this->responseError('The selected status is available in registered statuses');
        }
        try {
            DB::beginTransaction();
            $this->repository->update($billOfLading->getAttribute('id'), compact('status'));
            $this->actionLog($billOfLading, $status);
            if ($status === 'discharge') {
                $billOfLading->containers()->update(['on_shipping' => false]);
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
    ): bool|PendingDispatch|PendingClosureDispatch {
        return $this->smsService->send($billOfLading->getAttribute('car')->driver->phone, 'driver-enter-notify');
    }

    public function getTemplateBillOfLadingForVehicle(): BinaryFileResponse
    {
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
        $export = new TemplateExport($headers);
        return Excel::download($export, $this->repository->model->getTable().'-template-for-vehicle'.time().'.xlsx');
    }

    public function getTemplateBillOfLadingForContainer(): BinaryFileResponse
    {
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
        $export = new TemplateExport($headers);
        return Excel::download($export, $this->repository->model->getTable().'-template-for-container'.time().'.xlsx');
    }

    public function importBillOfLading(ImportBillOfLadingRequest $request): JsonResponse
    {

        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-'.$this->repository->model->getTable();
        $validatedData['data'] = [
            'file'                    => $validatedData['file'],
            'location_source_id'      => $validatedData['location_source_id'],
            'location_destination_id' => $validatedData['location_destination_id'],
            'type'                    => $validatedData['type'],
        ];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return response()->json([
            'message' => 'import job created', 'data' => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }
}
