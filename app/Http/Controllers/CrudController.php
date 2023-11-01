<?php

namespace App\Http\Controllers;

use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Filters\IdFilter;
use App\Filters\NameFilter;
use App\Filters\TitleFilter;
use App\Http\Resources\BaseResource;
use App\libs\BaseRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Pipeline;

class CrudController extends Controller
{
    public BaseRepository $repository;
    public                $resource = BaseResource::class;
    public array          $filters  = [
        NameFilter::class, TitleFilter::class, CreatedAtFromFilter::class, CreatedAtToFilter::class, IdFilter::class
    ];

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Pipeline::send($this->repository->model)->through($this->filters)->thenReturn()->paginate(request()->paginate ?? 15);
        return $this->collect($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
            ],500);
        }
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
     * Display the specified resource.
     */
    public function show($id)
    {
        return new $this->resource($this->repository->show($id));
    }

    /**
     * Update the specified resource in storage.
     */
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
            ],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }

    public function applyAuthorizeResource()
    {
        $this->authorizeResource($this->repository->model->getMorphClass(), $this->repository->model->getMorphClass());
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }
    public function collect($q)
    {
        return $this->resource::collection($q);
    }
}
