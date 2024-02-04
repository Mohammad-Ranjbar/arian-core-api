<?php

namespace App\libs;

use App\Filters\BlockByFilter;
use App\Filters\BlockItemMakeCodeIdFilter;
use App\Filters\BlockReasonIdFilter;
use App\Filters\CarModelIdFilter;
use App\Filters\CreatedAtFilter;
use App\Filters\CreatedAtFromFilter;
use App\Filters\CreatedAtToFilter;
use App\Filters\ElementIdFilter;
use App\Filters\EmailFilter;
use App\Filters\IdFilter;
use App\Filters\IncidentIdFilter;
use App\Filters\MakeCodeFilter;
use App\Filters\MakeCodesFilter;
use App\Filters\NameFilter;
use App\Filters\ProductionLineIdFilter;
use App\Filters\RolesFilter;
use App\Filters\SalonIdFilter;
use App\Filters\TitleFilter;
use App\Filters\TypeFilter;
use App\Filters\UnblockByFilter;
use App\Filters\UnblockedAtFilter;
use App\Filters\VinFilter;
use App\Filters\WorkGroupCenterIdFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Pipeline;


abstract class BaseRepository
{

    /**
     * @var Model
     */
    public Model $model;
    /**
     * @var array|string[]
     */
    public array $filters = [
        BlockItemMakeCodeIdFilter::class,
        BlockByFilter::class,
        BlockReasonIdFilter::class,
        CreatedAtFilter::class,
        UnblockByFilter::class,
        UnblockedAtFilter::class,
        EmailFilter::class,
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
        RolesFilter::class,
        MakeCodesFilter::class,
        MakeCodeFilter::class,

    ];
    /**
     * @var array|array[]
     */
    protected array $rules = ['create' => [], 'update' => [],];

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
        return $this->model->newQuery()->find($id)->delete();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->get()->map(function ($item) {
            return ['id' => $item->id, 'title' => $item->title,];
        })->all();
    }


    /**
     * @param  string  $orderBy
     * @param  string  $sort
     * @return mixed
     */
    public function get(string $orderBy = 'id', string $sort = 'asc'): mixed
    {
        return $this->makeQuery()->orderBy($orderBy, $sort)->get();
    }

    /**
     * @return mixed
     */
    public function makeQuery(): mixed
    {
        return Pipeline::send($this->model)->through($this->filters)->thenReturn();
    }


    /**
     * @param $data
     * @param  string  $parameter
     * @return Model|Builder
     */
    public function make($data, string $parameter = 'reference'): Model|Builder
    {
        return $this->model->query()->firstOrCreate([$parameter => $data[$parameter]], $data);
    }


    /**
     * @param $value
     * @param  string  $parameter
     * @return Model|Builder
     */
    public function findBy($value, string $parameter = 'reference'): Model|Builder
    {
        return $this->model->newQuery()->where($parameter, $value)->firstOrFail();
    }

    /**
     * @param $perPage
     * @return mixed
     */
    public function paginate($perPage,string $orderBy='id',string $orderType='asc'): mixed
    {
        return $this->makeQuery()->orderBy($orderBy,$orderType)->paginate($perPage);
    }


    /**
     * @param  array  $input
     * @return bool|Builder|Model|int
     */
    public function upsert(array $input): Model|Builder|bool|int
    {
        $record = $this->findWhere($input);
        if (!$record) {
            return $this->store($input);
        }
        return $record->update($input);
    }


    /**
     * @param  array  $where
     * @return Model|Builder|null
     */
    public function findWhere(array $where): null|Model|Builder
    {
        return $this->model->newQuery()->where($where)->first();
    }


    /**
     * @param $data
     * @return Model|Builder
     */
    public function store($data): Model|Builder
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
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @return array
     */
    public function getHeadings(): array
    {
        return $this->model->getHeadings() ?? [];
    }
}
