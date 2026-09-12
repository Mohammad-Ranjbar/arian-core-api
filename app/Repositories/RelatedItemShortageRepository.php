<?php

namespace App\Repositories;

use App\libs\BaseRepository;
use App\Models\MainItemShortage;
use App\Models\RelatedItemShortage;
use Illuminate\Support\Facades\DB;

class RelatedItemShortageRepository extends BaseRepository
{
    /**
     * @var array|array[]
     */
    protected array $rules = [
        'create' => [
            'main_item_shortage_id' => 'required|exists:main_item_shortages,id',
            'item_no'               => 'required|string|max:200',
            'description'           => 'required|string',
        ]
    ];

    public array $searchTerms = [
        'item_no',
        'description',
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
     */
    public function store($data)
    {
        $main = $this->mainItemShortage($data['main_item_shortage_id']);
        if (!$main->isDraft()) {
            $main = $main->forkVersion();
        }

        return $main->relatedItems()->create([
            'item_no'     => $data['item_no'],
            'description' => $data['description'],
        ]);
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
                return (bool) $item->delete();
            }
            $main->forkVersion([$item->id]);
            return true;
        });
    }

    private function mainItemShortage($id): MainItemShortage
    {
        return MainItemShortage::query()->findOrFail($id);
    }
}
