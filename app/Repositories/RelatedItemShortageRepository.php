<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use Illuminate\Support\Facades\DB;
use Throwable;

class RelatedItemShortageRepository extends BaseRepository
{
    public array $searchTerms = [
        'item_no',
        'description',
    ];
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'main_item_shortage_id' => 'required|exists:main_item_shortages,id',
            'item_no' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'location_code' => 'required|string|max:200',
            'data' => 'required|array'
        ]
    ];

    /**
     * @param RelatedItemShortage $model
     */
    public function __construct(RelatedItemShortage $model)
    {
        $this->model = $model;
    }

    public function makeQuery(): mixed
    {
        $q = parent::makeQuery();
        if (request()->filled('main_item_shortage_id')) {
            $q = $q->where('main_item_shortage_id', request()->integer('main_item_shortage_id'));
        }
        return $q;
    }

    /**
     * A draft main record takes the item directly; an active one is frozen, so
     * the item lands on a fresh fork of it.
     * @throws Throwable
     */
    public function store($data): MainItemShortage
    {
        return DB::transaction(function () use ($data): MainItemShortage {
            $main = $this->mainItemShortage($data['main_item_shortage_id']);
            if (!$main->isDraft() || $main->getAttribute('location_code') !== $data['location_code']) {
                $main = $main->forkVersion($data['location_code']);
            }
            if (filled($data['item_no'] ?? null) && filled($data['description'] ?? null)) {
                $main->relatedItems()->create([
                    'item_no' => $data['item_no'],
                    'description' => $data['description'],
                    'data' => $data['data'],
                ]);
            }

            return $main->refresh();
        });
    }

    private function mainItemShortage($id): MainItemShortage
    {
        return MainItemShortage::query()->findOrFail($id);
    }

    /**
     * Same rule on removal: delete from a draft, fork without the item from an
     * active record. CrudController wraps store in a transaction but not destroy.
     */
    public function destroy($id): bool
    {
        /** @var RelatedItemShortage $item */
        $item = $this->model->newQuery()->findOrFail($id);

        return DB::transaction(function () use ($item) {
            $main = $this->mainItemShortage($item->main_item_shortage_id);
            if ($main->isDraft()) {
                return (bool)$item->delete();
            }
            $main->forkVersion($main->location_code, [$item->id]);
            return true;
        });
    }
}
