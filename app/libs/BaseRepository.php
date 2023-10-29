<?php

namespace App\libs;

class BaseRepository
{

    public $model;
    public $rules;

    public $resource;

    public function setModel($model)
    {
        $this->model = app()->make($model);
    }

    public function getModel()
    {
        return $this->model;
    }

    public function paginate()
    {
        return $this->getModel()->query()->paginate();
    }

    public function store($data)
    {
        return $this->getModel()->query()->create($data);
    }

    public function show($id)
    {
        return $this->getModel()->find($id);
    }

    public function update($id, $data)
    {
        return $this->getModel()->find($id)->update($data);
    }

    public function destroy($id)
    {
        return $this->getModel()->find($id)->delete();
    }

    public function setStoreValidation(array $rules)
    {
        $this->rules = $rules;
    }

    public function setUpdateValidation(array $rules)
    {
        $this->rules = $rules;
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