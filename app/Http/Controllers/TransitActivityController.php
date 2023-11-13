<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExitPermitRequest;
use App\Http\Requests\TransitActivitySetPriorityRequest;
use App\Http\Resources\TransitActivityResource;
use App\Models\TransitActivity;
use App\Repositories\TransitActivityRepository;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransitActivityController extends CrudController
{

    public function __construct(private readonly  TransitActivityRepository $repository,private readonly  SmsService $smsService)
    {
        $this->applyAuthorizeResource();
        $this->setResource(TransitActivityResource::class);
    }


    /**
     * @param  Request  $request
     * @return TransitActivityResource|JsonResponse
     */
    public function store(Request $request): TransitActivityResource|JsonResponse
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
        try {
            $validatedData['user_id'] = Auth::id();
            $saveItem = $this->repository->store($validatedData);
            return new TransitActivityResource($saveItem);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @param  TransitActivitySetPriorityRequest  $request
     * @return TransitActivityResource|JsonResponse
     */
    public function setPriority(
        TransitActivity $transitActivity,
        TransitActivitySetPriorityRequest $request
    ): TransitActivityResource|JsonResponse {
        try {
            $this->authorize('transit-activities-set-priority');
            $validatedData = $request->validated();
            $transitActivity->update($validatedData);
            return new TransitActivityResource($transitActivity);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     */
    public function temporaryReceipt(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        try {
            $this->authorize('transit-activities-set-temporary-receipt');
            if ($transitActivity->statuses()->count() == 0) {
                $transitActivity->statuses()->create([
                    'user_id' => Auth::id(),
                    'status'  => 'temporary-receipt'
                ]);
                $transitActivity->update(['status' => 'temporary-receipt']);
                return new TransitActivityResource($transitActivity);
            }
            return response()->json([
                'message' => 'can not do this action'
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     */
    public function enter(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        try {
            $this->authorize('transit-activities-set-enter');
            if (!$transitActivity->statuses()->where('status', 'enter')->exists() &&
                $transitActivity->statuses()->where('status', 'temporary-receipt')->exists()
            ) {
                $transitActivity->statuses()->create([
                    'user_id' => Auth::id(),
                    'status'  => 'enter'
                ]);
                $transitActivity->update(['status' => 'enter']);
                return new TransitActivityResource($transitActivity);
            }
            return response()->json([
                'message' => 'can not do this action'
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     */
    public function discharge(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        try {
            $this->authorize('transit-activities-set-discharge');
            if (!$transitActivity->statuses()->where('status', 'discharge')->exists() &&
                $transitActivity->statuses()->where('status', 'enter')->exists()
            ) {
                $transitActivity->statuses()->create([
                    'user_id' => Auth::id(),
                    'status'  => 'discharge'
                ]);
                $transitActivity->update(['status' => 'discharge']);
                return new TransitActivityResource($transitActivity);
            }
            return response()->json([
                'message' => 'can not do this action'
            ]);

        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
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
        try {
            $this->authorize('transit-activities-set-exit-permit');
            if ($transitActivity->statuses()->where('status', 'exit-permit')->exists() ||
                $transitActivity->statuses()->where('status', 'exit')->exists()
            ) {
                return response()->json([
                    'message' => 'can not exit the container'
                ]);
            }
            if ($validatedData['exit_by']) {
                $record = $this->repository->show($validatedData['exit_by']);
                if (
                    !$transitActivity->statuses()->where('status', 'discharge')->exists() ||
                    !$record->statuses()->where('status', 'discharge')->exists() ||
                    $record->statuses()->where('status', 'exit-container')->exists()
                ) {
                    return response()->json([
                        'message' => 'can not exit the container'
                    ]);
                }
            }
            $transitActivity->statuses()->where(['status' => 'exit'])->exists();
            $transitActivity->statuses()->create($validatedData);
            unset($validatedData['user_id']);
            $transitActivity->update($validatedData);
            return new TransitActivityResource($transitActivity);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param  TransitActivity  $transitActivity
     * @return JsonResponse|TransitActivityResource
     */
    public function exit(TransitActivity $transitActivity): JsonResponse|TransitActivityResource
    {
        try {
            $this->authorize('transit-activities-set-exit');
            if (!$transitActivity->statuses()->where('status', 'exit-permit')->exists() ||
                $transitActivity->statuses()->where('status', 'exit')->exists()
            ) {
                return response()->json([
                    'message' => 'can not exit the container'
                ]);
            }

            $transitActivity->statuses()->create([
                'user_id' => Auth::id(),
                'status'  => 'exit'
            ]);

            $transitActivity->update(['status' => 'exit']);
            if ($transitActivity->exit_by) {
                $this->repository->show($transitActivity->exit_by)->statuses()->create([
                    'user_id' => Auth::id(),
                    'status'  => 'exit-container'
                ]);
            }
            return new TransitActivityResource($transitActivity);
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }


    /**
     * @param  TransitActivity  $transitActivity
     * @return bool|JsonResponse
     */
    public function notify(TransitActivity $transitActivity): bool|JsonResponse
    {
        try {
            $this->authorize('transit-activities-notify');
            $phone = $transitActivity->transitDriver->phone;
            $this->smsService->send($phone, 'driver-enter-notify');
            return true;
        } catch (Exception $exception) {
            return response()->json([
                'message' => $exception->getMessage()
            ]);
        }

    }

}
