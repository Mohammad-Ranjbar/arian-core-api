<?php

namespace App\Http\Controllers;

use App\Http\Resources\BaseResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrudController extends Controller
{

    /**
     * @var string
     */
    public string $resource = BaseResource::class;
    /**
     * @var bool
     */
   protected  bool $appendUserId = false;



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
        $validatedData = $request->validate($this->repository->getCreateRules());
        if ($this->appendUserId){
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
        return $this->doUpdate($id,$validatedData);
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


}
