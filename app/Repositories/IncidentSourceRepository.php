<?php

namespace App\Repositories;

use App\Filters\ModelIdFilter;
use App\libs\BaseRepository;
use App\Models\IncidentSource;
use App\Models\IncidentSourceSelection;

class IncidentSourceRepository extends BaseRepository
{
    public function __construct(IncidentSource $model)
    {
        $this->model = $model;

    }

    public function getFilters(): array
    {
        // This repository filters model IDs through selections, not the legacy column.
        return array_values(array_filter(parent::getFilters(), fn ($filter) => $filter !== ModelIdFilter::class));
    }

    public function paginateForResource(int $perPage, array $filters = []): mixed
    {
        $query = $this->makeQuery()->with(IncidentSource::resourceRelations());

        if (isset($filters['model_type']) || isset($filters['model_id'])) {
            $query->whereHas('selections', function ($query) use ($filters) {
                if (isset($filters['model_type'])) {
                    $model = new (IncidentSourceSelection::MODEL_TYPES[$filters['model_type']]);
                    $query->where('model_type', $model->getMorphClass());
                }
                if (isset($filters['model_id'])) {
                    $query->where('model_id', (int) $filters['model_id']);
                }
            });
        }

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }
}
