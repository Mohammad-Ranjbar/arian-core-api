<?php

namespace App\libs;

use App\Filters\CarModelIdFilter;
use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Filters\ElementIdFilter;
use App\Filters\IdFilter;
use App\Filters\IncidentIdFilter;
use App\Filters\NameFilter;
use App\Filters\ProductionLineIdFilter;
use App\Filters\SalonIdFilter;
use App\Filters\TitleFilter;
use App\Filters\TypeFilter;
use App\Filters\VinFilter;
use App\Filters\WorkGroupCenterIdFilter;
use Illuminate\Support\Facades\Pipeline;

abstract class BaseRepository
{
    /**
     * @var
     */
    public       $model;
    public array $filters = [
        NameFilter::class,
        TitleFilter::class,
        CreatedAtFromFilter::class,
        CreatedAtToFilter::class,
        IdFilter::class,
        SalonIdFilter::class,
        ProductionLineIdFilter::class,
        WorkGroupCenterIdFilter::class,
        VinFilter::class,
        TypeFilter::class,
        CarModelIdFilter::class,
        ElementIdFilter::class,
        IncidentIdFilter::class,
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [], 'update' => [],
    ];

    /**
     * @return array
     */
    public function getCreateRules(): array
    {
        return $this->rules['create'];
    }

    /**
     * @return array
     */
    public function getUpdateRules(): array
    {
        return $this->rules['update'];
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id): bool
    {
        return $this->model->find($id)->delete();
    }

    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }

    public function get()
    {
        return $this->makeQuery()->get();
    }

    public function makeQuery()
    {
        return Pipeline::send($this->model)
            ->through($this->filters)
            ->thenReturn();
    }

    public function make($data, $parameter = 'reference')
    {
        $record = $this->findBy($data[$parameter], $parameter);
        if (!$record) {
            $record = $this->store($data);
        }
        return $record;
    }

    public function findBy($value, $parameter = 'reference')
    {
        return $this->model->newQuery()->where($parameter, $value)->first();
    }

    /**
     * @param $data
     * @return mixed
     */
    public function store($data): mixed
    {
        return $this->model->query()->create($data);
    }

    public function paginate($perPage)
    {
        return $this->makeQuery()->paginate($perPage);
    }

    public function upsert(array $input)
    {
        $record = $this->findWhere($input);
        if (!$record) {
            return $this->store($input);
        }
        return $record->update($input);
    }

    public function findWhere(array $where)
    {
        return $this->model->newQuery()->where($where)->first();
    }

    /**
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data): mixed
    {
        $model = $this->show($id);
        $model->update($data);
        return $model;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id): mixed
    {
        return $this->model->findOrFail($id);
    }
}