<?php

namespace App\Http\Controllers;

use App\Http\Resources\BaseResource;
use App\libs\BaseRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrudController extends Controller
{

    /**
     * @var string
     */
    public string $resource = BaseResource::class;


    /**
     * @param  Request  $request
     * @return mixed
     */
    public function index(Request $request): mixed
    {
        $perPage = $request->get('per_page', 15);
        return $this->collect($this->repository->paginate($perPage));
    }

    /**
     * @param $q
     * @return mixed
     */
    public function collect($q): mixed
    {
//        TODO
        return $this->resource::collection($q);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse|mixed
     */
    public function store(Request $request): mixed
    {
        $validatedData = $request->validate($this->repository->getCreateRules());
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

    /**
     * @param $record
     * @param $data
     * @return mixed
     */
    public function afterSave($record, $data): mixed
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
        try {
            DB::beginTransaction();
            $model = $this->repository->update($id, $validatedData);
            $model = $this->afterSave($model, $validatedData);
            DB::commit();
            return new $this->resource($model);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'message' => $exception->getMessage()
            ], 500);
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id): bool
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
}
