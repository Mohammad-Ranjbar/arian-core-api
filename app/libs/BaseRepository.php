<?php

namespace App\libs;

abstract class BaseRepository
{
    /**
     * @var
     */
    public $model;

    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [], 'update' => [],
    ];

    /**
     * @return array
     */
    public function getCreateRules():array
    {
        return $this->rules['create'];
    }

    /**
     * @return array
     */
    public function getUpdateRules():array
    {
        return $this->rules['update'];
    }

    /**
     * @return mixed
     */
    public function paginate():mixed
    {
        return $this->model->query()->paginate();
    }

    /**
     * @param $data
     * @return mixed
     */
    public function store($data):mixed
    {
        return $this->model->query()->create($data);
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data):mixed
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id):mixed
    {
        return $this->model->find($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id):bool
    {
        return $this->model->find($id)->delete();
    }
    public function getOptions():array
    {
        return $this->model->newQuery()->get()->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }
}