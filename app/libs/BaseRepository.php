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

    public $model;
    /**
     * @var array|string[]
     */
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

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return [
                'id'    => $item->id,
                'title' => $item->title,
            ];
        })->all();
    }

    /**
     * @return mixed
     */
    public function get(): mixed
    {
        return $this->makeQuery()->get();
    }

    /**
     * @return mixed
     */
    public function makeQuery(): mixed
    {
        return Pipeline::send($this->model)
            ->through($this->filters)
            ->thenReturn();
    }

    /**
     * @param $data
     * @param  string  $parameter
     * @return mixed
     */
    public function make($data, string $parameter = 'reference'): mixed
    {
        return $this->model->query()->firstOrCreate([$parameter => $data[$parameter]], $data);
    }

    /**
     * @param $value
     * @param  string  $parameter
     * @return mixed
     */
    public function findBy($value, string $parameter = 'reference'): mixed
    {
        return $this->model->newQuery()->where($parameter, $value)->first();
    }

    /**
     * @param $perPage
     * @return mixed
     */
    public function paginate($perPage): mixed
    {
        return $this->makeQuery()->paginate($perPage);
    }

    /**
     * @param  array  $input
     * @return mixed
     */
    public function upsert(array $input): mixed
    {
        $record = $this->findWhere($input);
        if (!$record) {
            return $this->store($input);
        }
        return $record->update($input);
    }

    /**
     * @param  array  $where
     * @return mixed
     */
    public function findWhere(array $where): mixed
    {
        return $this->model->newQuery()->where($where)->first();
    }

    /**
     * @param $data
     * @return mixed
     */
    public function store($data): mixed
    {
        return $this->model->query()->create($data);
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

    public function getHeadings(): array
    {
        return $this->model->getHeadings() ?? [];
    }
}