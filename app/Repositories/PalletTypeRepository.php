<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\PalletType;

class PalletTypeRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'title' => 'required|string|max:255',
        ], 'update' => [
            'title' => 'required|string|max:255',
        ]
    ];

    /**
     * @param  PalletType  $model
     */
    public function __construct(PalletType $model)
    {
        $this->model = $model;
    }

    /**
     * @param $id
     * @return bool
     */
    public function destroy($id): bool
    {
        $model = $this->model->newQuery()->find($id);
        if ($model->pallets()->count()) {
            return false;
        }
        return $model->delete();
    }

}
