<?php

namespace App\Http\Controllers;

use App\Exports\TemplateExport;
use App\Http\Requests\JobRequest;
use App\Http\Resources\BaseResource;
use App\Jobs\ImportJob;
use App\Models\Job;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class CrudController extends Controller
{
    /**
     * @var string
     */
    public string $resource = BaseResource::class;
    protected array $appendStoreData = [];
    /**
     * @var bool
     */
    protected bool $appendUserId = false;


    public function index(Request $request): JsonResponse |AnonymousResourceCollection
    {
        $perPage = $request->get('per_page', 15);
        if (isset($this->repository)) {
            return $this->collect($this->repository->paginate($perPage));
        }
        return $this->responseError('repository not found',400);
    }

    public function collect($q)
    {
//        TODO
        return $this->resource::collection($q);
    }


    public function store(Request $request)
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(), $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            DB::commit();

            return new $this->resource($saveItem);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function customStoreRules(): array
    {
        return [];
    }

    public function afterSave($record, $data)
    {
        foreach ($data as $key => $input) {
            if (method_exists($record, $key) and is_array($input)) {
                $record->{$key}()->sync($input);
            }
        }
        return $record;
    }


    public function show($id)
    {
        if (isset($this->repository)) {
            return new $this->resource($this->repository->show($id));
        }
        return $this->responseError('repository not found',400);
    }

    public function update(Request $request, $id)
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $validatedData = $request->validate($this->repository->getUpdateRules());
        return $this->doUpdate($id, $validatedData);
    }

    public function doUpdate($id, $data)
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        try {
            DB::beginTransaction();
            $model = $this->repository->update($id, $data);
            $model = $this->afterSave($model, $data);
            DB::commit();
            return new $this->resource($model);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        if($this->repository->destroy($id)){
            return response()->json(['message' => 'item deleted!']);
        }
        return $this->responseError( 'امکان حذف آیتم مورد نظر وجود ندارد.');
    }


    public function applyAuthorizeResource(): bool|JsonResponse
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $this->authorizeResource($this->repository->model->getMorphClass(), $this->repository->model->getMorphClass());

        return true;
    }

    /**
     * @param $resource
     * @return void
     */
    public function setResource($resource): void
    {
        $this->resource = $resource;
    }

    /**
     * @param JobRequest $request
     * @return JsonResponse
     */
    public function import(JobRequest $request): JsonResponse
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-' . $this->repository->model->getTable();
        $validatedData['data'] = [$request->all()];
        $job = Job::query()->create($validatedData);

        ImportJob::dispatch($job->getAttribute('uuid'));
        return response()->json(['message' => 'import job created', 'data' => ['uuid' => $job->getAttribute('uuid'), 'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))]]);
    }

    public function getTemplate(): BinaryFileResponse|JsonResponse
    {
        if (! isset($this->repository)) {
            return $this->responseError('repository not found',400);
        }
        $headers = $this->repository->getHeadings();
        $export = new TemplateExport($headers);
        return Excel::download($export, $this->repository->model->getTable() . '-template.xlsx');
    }


}
