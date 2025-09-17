<?php

namespace App\Http\Controllers;

use App\Http\Resources\PDIResource;
use App\Models\Vehicle;
use App\Repositories\PDIRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PDIController extends CrudController
{

    protected bool $appendUserId = true;

    public function __construct(public readonly PDIRepository $repository)
    {
        $this->applyAuthorizeResource();
        $this->setResource(PDIResource::class);
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        $validatedData['vin'] = $request->input('vehicle');
        $vehicle = Vehicle::query()->where('vin', $validatedData['vin'])->first();
        if (!$vehicle) {
            throw new ValidationException('شماره VIN وارد شده یافت نشد');
        }

        if (empty($vehicle->carModel->production)) {
            throw new Exception("لطفا برای مدل خودرو {$vehicle->getAttribute('carModel')->title} نوع تولید را مشخص کنید. ");
        }
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);

            DB::commit();
            return new $this->resource($saveItem);
        } catch (Throwable $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

}
