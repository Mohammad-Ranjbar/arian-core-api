<?php

namespace App\libs;

abstract class BaseRepository
{
    public $model;

    protected array $rules = [
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

    public function update($id, $data)
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function destroy($id)
    {
        return $this->model->find($id)->delete();
    }
}