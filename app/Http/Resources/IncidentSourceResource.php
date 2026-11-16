<?php

namespace App\Http\Resources;

use App\Models\IncidentSourceSelection;
use App\Models\WorkGroupCenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sources = $this->resource->selections->map(function ($selection) {
            $model = $selection->model;
            $productionLine = $model instanceof WorkGroupCenter ? $model->ProductionLine : null;

            return [
                'id' => $selection->id,
                'model_type' => $selection->model?->getTable() ?? (array_search($selection->model_type, IncidentSourceSelection::MODEL_TYPES, true) ?: ''),
                'model_id' => $selection->model_id,
                'source' => $model?->title ?? '',
                'production_line' => $productionLine ? [
                    'id' => $productionLine->id,
                    'title' => $productionLine->title,
                ] : null,
                'user_id' => $selection->user_id,
                'user' => $selection->user ? [
                    'id' => $selection->user->id,
                    'name' => $selection->user->name,
                ] : null,
                'created_at' => $selection->created_at,
            ];
        })->values();
        $first = $sources->first();

        return [
            'id' => $this->resource->id,
            'car_model_id' => $this->resource->car_model_id,
            'element_id' => $this->resource->element_id,
            'incident_id' => $this->resource->incident_id,
            'carModel' => [
                'id' => $this->resource->carModel?->id,
                'title' => $this->resource->carModel?->title,
            ],
            'element' => [
                'id' => $this->resource->element?->id,
                'title' => $this->resource->element?->title,
            ],
            'incident' => [
                'id' => $this->resource->incident?->id,
                'title' => $this->resource->incident?->title,
            ],

            'sources' => $sources,
            // Preserve the legacy response keys using the first selection.
            'model_type' => $first['model_type'] ?? '',
            'model_id' => $first['model_id'] ?? '',
            'source' => $first['source'] ?? '',
        ];
    }
}
