<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\ProductionLine;

class ProductionLineRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'title' => 'required|string|unique:production_lines,title',
            'salon_id' => 'required|exists:salons,id',
            'teams_webhook' => 'nullable',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
            'location_code' => 'nullable|string',
            'prefix' => 'nullable|string',
            'show_in_board' => 'required|boolean',
            'location' => 'nullable|string',
            'central_location' => 'nullable|string',
            'item_type' => 'required|in:0,1,2'
        ],
        'update' => [
            'title' => 'required|string|unique:production_lines,title,',
            'salon_id' => 'required|exists:salons,id',
            'teams_webhook' => 'nullable',
            'on_enter' => 'nullable|boolean',
            'on_exit' => 'nullable|boolean',
            'location_code' => 'nullable|string',
            'prefix' => 'nullable|string',
            'show_in_board' => 'required|boolean',
            'location' => 'nullable|string',
            'central_location' => 'nullable|string',
            'item_type' => 'required|in:0,1,2'
        ]
    ];

    /**
     * @param ProductionLine $model
     */
    public function __construct(ProductionLine $model)
    {
        $this->model = $model;
        if (request()->route()) {
            $this->rules['update']['title'] .= request()->route()->parameter('production_line');
        }
    }

    public function getOptions(): array
    {
        $url = request()->getRequestUri()  ;

        $type = match ($url) {
            '/api/options/production-engine-lines' => 'engine',
            default => 'vehicle'
        };

        return $this->model->newQuery()->where('production_line_type', $type)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title . ' - ' . $item->code,
            ];
        })->all();
    }

}
