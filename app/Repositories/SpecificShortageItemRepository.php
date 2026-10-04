<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\SpecificShortageItem;
use Illuminate\Support\Facades\DB;

class SpecificShortageItemRepository extends BaseRepository
{
    public array $searchTerms = [
        'qc_code',
        'item_no',
        'description',
    ];

    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'item_no' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'erp_product_item_id' => 'required|integer|exists:erp_product_items,id',
            'car_model_id' => 'required|integer|exists:car_models,id',
            'is_active' => 'required|boolean',
        ],
        'update' => [
            'item_no' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'erp_product_item_id' => 'required|integer|exists:erp_product_items,id',
            'car_model_id' => 'required|integer|exists:car_models,id',
            'is_active' => 'required|boolean',
        ],
    ];

    public function __construct(SpecificShortageItem $model)
    {
        $this->model = $model;
    }

    public function makeQuery(): mixed
    {
        return parent::makeQuery()->with(['erpProductItem', 'carModel', 'actionLogs.user.stations']);
    }

    public function store($data): SpecificShortageItem
    {
        /** @var SpecificShortageItem $item */
        $item = parent::store($data);

        return $item->load(['erpProductItem', 'carModel', 'actionLogs.user.stations']);
    }

    public function update($id, $data): SpecificShortageItem
    {
        /** @var SpecificShortageItem $item */
        $item = parent::update($id, $data);

        return $item->load(['erpProductItem', 'carModel', 'actionLogs.user.stations']);
    }

    public function show($id): SpecificShortageItem
    {
        return $this->model->newQuery()
            ->with(['erpProductItem', 'carModel', 'actionLogs.user.stations'])
            ->findOrFail($id);
    }

    public function destroy($id): bool
    {
        return DB::transaction(fn () => parent::destroy($id));
    }
}
