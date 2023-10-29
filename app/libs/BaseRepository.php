<?php

namespace App\libs;

use App\Http\Resources\BaseResource;

abstract class BaseRepository
{
    public          $model;
    public          $resource = BaseResource::class;
    protected array $rules    = [
        'create' => [], 'update' => [],
    ];

    public function getCreateRules()
    {
        return $this->rules['create'];
    }

    public function getUpdateRules()
    {
        return $this->rules['update'];
    }

    public function paginate()
    {
        return $this->model->query()->paginate();
    }

    public function store($data)
    {
        return $this->model->query()->create($data);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($id, $data)
    {
        return $this->model->find($id)->update($data);
    }

    public function destroy($id)
    {
        return $this->model->find($id)->delete();
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