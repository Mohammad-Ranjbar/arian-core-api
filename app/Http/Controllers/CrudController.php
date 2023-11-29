<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRequest;
use App\Http\Resources\BaseResource;
use App\Jobs\ImportJob;
use App\Models\Job;
use App\Services\Importer\ImporterServiceFactory;
use App\Services\Importer\ImportIncidentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrudController extends Controller
{
    /**
     * @var string
     */
    public string   $resource        = BaseResource::class;
    protected array $appendStoreData = [];
    /**
     * @var bool
     */
    protected bool $appendUserId = false;


    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        return $this->collect($this->repository->paginate($perPage));
    }

    public function collect($q)
    {
//        TODO
        return $this->resource::collection($q);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate(array_merge($this->repository->getCreateRules(),
            $this->customStoreRules()));
        $validatedData = array_merge($validatedData, $this->appendStoreData);
        if ($this->appendUserId) {
            $validatedData['user_id'] = Auth::id();
        }
        try {
            DB::beginTransaction();
            $saveItem = $this->repository->store($validatedData);
            $saveItem = $this->afterSave($saveItem, $validatedData);
            $data = new $this->resource($saveItem);
            DB::commit();
            return $data;
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
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

    /**
     * @param $id
     * @return mixed
     */
    public function show($id): mixed
    {
        return new $this->resource($this->repository->show($id));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate($this->repository->getUpdateRules());
        return $this->doUpdate($id, $validatedData);
    }

    public function doUpdate($id, $data)
    {
        try {
            DB::beginTransaction();
            $model = $this->repository->update($id, $data);
            $model = $this->afterSave($model, $data);
            DB::commit();
            return new $this->resource($model);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    /**
     * @return void
     */
    public function applyAuthorizeResource(): void
    {
        $this->authorizeResource($this->repository->model->getMorphClass(), $this->repository->model->getMorphClass());
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
     * @param  JobRequest  $request
     * @return JsonResponse
     */
    public function import(JobRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = Auth::id();
        $validatedData['type'] = 'import-'.$this->repository->model->getTable();
        $validatedData['data'] = [
            'file' => $validatedData['file']
        ];
        $job = Job::query()->create($validatedData);
        ImporterServiceFactory::getInstance($job->getAttribute('type'));

        return response()->json([
            'message' => 'import job created',
            'data'    => [
                'uuid'         => $job->getAttribute('uuid'),
                'tracking_url' => route('job.tracking', $job->getAttribute('uuid'))
            ]
        ]);
    }

}
