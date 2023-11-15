<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExitPermitRequest;
use App\Http\Requests\TransitActivitySetPriorityRequest;
use App\Http\Resources\TransitActivityResource;
use App\Models\TransitActivity;
use App\Repositories\TransitActivityRepository;
use App\Services\SmsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class TransitActivityController extends CrudController
{
    /**
     * @var bool
     */
    protected bool $appendUserId = true;

    /**
     * @param  TransitActivityRepository  $repository
     * @param  SmsService  $smsService
     */
    public function __construct(
        private readonly TransitActivityRepository $repository,
        private readonly SmsService $smsService
    ) {
        $this->applyAuthorizeResource();
        $this->setResource(TransitActivityResource::class);
    }


    /**
     * @param  TransitActivity  $transitActivity
     * @param  TransitActivitySetPriorityRequest  $request
     * @return TransitActivityResource|JsonResponse
     * @throws AuthorizationException
     */
    public function setPriority(
        TransitActivity $transitActivity,
        TransitActivitySetPriorityRequest $request
    ): TransitActivityResource|JsonResponse {
        $this->authorize('transit-activities-set-priority');
        return $this->doUpdate($transitActivity->id, $request->validated());
    }


    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     * @throws AuthorizationException
     */
    public function temporaryReceipt(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        $this->authorize('transit-activities-set-temporary-receipt');
        if ($transitActivity->statuses()->count() > 0) {
            return $this->responseError('can not do this action');
        }
        $transitActivity->setStatus('temporary-receipt');
        return new TransitActivityResource($transitActivity);
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     * @throws AuthorizationException
     */
    public function enter(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        $this->authorize('transit-activities-set-enter');
        if (!$transitActivity->hasStatus('enter') &&
            $transitActivity->hasStatus('temporary-receipt')
        ) {
            $transitActivity->setStatus('enter');
            return new TransitActivityResource($transitActivity);
        }
        return $this->responseError('can not do this action');
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     * @throws AuthorizationException
     */
    public function discharge(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        $this->authorize('transit-activities-set-discharge');
        if (!$transitActivity->hasStatus('discharge') &&
            $transitActivity->hasStatus('enter')
        ) {
            $transitActivity->setStatus('discharge');
            return new TransitActivityResource($transitActivity);
        }
        return $this->responseError('can not do this action');
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @param  ExitPermitRequest  $request
     * @return JsonResponse|TransitActivityResource
     */
    public function exitPermit(
        TransitActivity $transitActivity,
        ExitPermitRequest $request
    ): JsonResponse|TransitActivityResource {
        $validatedData = $request->validated();
        if ($transitActivity->hasStatus('exit-permit') || $transitActivity->hasStatus('exit')) {
            return $this->responseError('can not exit the container');
        }
        if ($validatedData['exit_by']) {
            $record = $this->repository->show($validatedData['exit_by']);
            if (!$transitActivity->hasStatus('discharge') || !$record->hasStatus('discharge') || $record->hasStatus('exit-container') || $record->parent) {
                return $this->responseError('can not exit the container');
            }
            $transitActivity->update(['exit_by' => $record->id]);
        }
        $transitActivity->setStatus('exit-permit');
        return new TransitActivityResource($transitActivity);
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     * @throws AuthorizationException
     */
    public function exit(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        $this->authorize('transit-activities-set-exit');
        if (!$transitActivity->hasStatus('exit-permit') || $transitActivity->hasStatus('exit')) {
            return $this->responseError('can not exit the container');
        }
        $transitActivity->setStatus('exit');
        if ($transitActivity->child) {
            $transitActivity->child->createStatusLog('exit-container');
        }
        return new TransitActivityResource($transitActivity);
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return bool|JsonResponse
     * @throws AuthorizationException
     */
    public function notify(TransitActivity $transitActivity): bool|JsonResponse
    {
        $this->authorize('transit-activities-notify');
        $phone = $transitActivity->transitDriver->phone;
        return $this->smsService->send($phone, 'driver-enter-notify');
    }

}
