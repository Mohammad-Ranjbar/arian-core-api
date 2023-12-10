<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetPriorityRequest;
use App\Http\Resources\BillOfLadingResource;
use App\Models\BillOfLading;
use App\Repositories\BillOfLadingRepository;
use App\Services\SmsService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        if ($data['type'] == 'containers') {
            $record->containers()->sync($data['items']);
            $record->containers()->update(['on_shipping' => true]);
        }
        $record->permits()->forceDelete();

        $record->car->gatePermits()->create([
            'type' => 'enter',
            'owner_id' => $record->id,
            'owner_type' => $record->getMorphClass()
        ]);

        $action = $record->wasRecentlyCreated ? 'new' : 'edit';
        $record->actionLogs()->create(['user_id' => Auth::id(), 'action' => $action]);

        return $record;
    }

    public function customStoreRules(): array
    {
        return [
            'items.*' => Rule::exists('containers', 'id')->where('on_shipping', false),
        ];
    }

    public function destroy($id)
    {
        $model = $this->repository->show($id);
        if ($model->status != 'new') {
            return $this->responseError('Can not delete this item', 400);
        }

        $model->containers()->update(['on_shipping' => false]);
        $model->containers()->detach();
        $model->permits()->forceDelete();
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

    public function notice(BillOfLading $billOfLading)
    {
        return $this->smsService->send($billOfLading->getAttribute('car')->driver->phone, 'driver-enter-notify');
    }
}
