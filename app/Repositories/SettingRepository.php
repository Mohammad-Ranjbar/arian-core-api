<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\Setting;

class SettingRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create'    => [
            'key'   => 'required|string|max:255|unique:settings,key',
            'value'   => 'required|string|max:255',
        ], 'update' => [
            'key'   => 'required|string|max:255|unique:settings,key,',
            'value'   => 'required|string|max:255',
        ]
    ];

    /**
     * @param  Setting  $model
     */
    public function __construct(Setting $model)
    {
        if (request()->route()) {
            $this->rules['update']['key'] .= request()->route()->parameter('setting');
        }
        $this->model = $model;
    }

}
