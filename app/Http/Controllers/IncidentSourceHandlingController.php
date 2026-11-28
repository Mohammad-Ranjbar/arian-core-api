<?php

namespace App\Http\Controllers;

use App\Models\IncidentSource;
use App\Models\IncidentSourceHandling;
use App\Models\IncidentSourceSelection;
use App\Models\Location;
use App\Models\VehicleIncident;
use App\Models\WorkGroupCenter;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncidentSourceHandlingController extends Controller
{
    public function index(IncidentSource $incidentSource): JsonResponse
    {
        $handlings = IncidentSourceHandling::query()
            ->where('incident_source_id', $incidentSource->id)
            ->with(['action.user:id,name', 'events.user:id,name', 'model' => function (MorphTo $morphTo) {
                $morphTo->constrain([
                    Location::class => fn ($query) => $query->select(['id', 'title']),
                    WorkGroupCenter::class => fn ($query) => $query->withTrashed()->select(['id', 'title']),
                ]);
            }])
            ->orderBy('id')->get();

        $handlings->each(function (IncidentSourceHandling $handling) {
            $handling->setAttribute('title', $handling->model?->title);
            $handling->unsetRelation('model');
        });

        return response()->json(['data' => $handlings]);
    }

    public function vins(Request $request, IncidentSource $incidentSource): JsonResponse
    {
        $data = $request->validate([
            'paginate' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $vins = VehicleIncident::query()
            ->where('incident_id', $incidentSource->incident_id)
            ->where('element_id', $incidentSource->element_id)
            ->select('vin')->distinct('vin')->orderBy('vin')
            ->paginate((int) ($data['paginate'] ?? 15))->withQueryString();

        return response()->json($vins);
    }

    public function viewed(Request $request, IncidentSource $incidentSource, IncidentSourceSelection $selection): JsonResponse
    {
        return $this->transition($request, $incidentSource, $selection, 'viewed');
    }

    public function start(Request $request, IncidentSource $incidentSource, IncidentSourceSelection $selection): JsonResponse
    {
        return $this->transition($request, $incidentSource, $selection, 'started');
    }

    public function action(Request $request, IncidentSource $incidentSource, IncidentSourceSelection $selection): JsonResponse
    {
        $data = $request->validate(['description' => ['required', 'string', 'max:10000']]);

        return $this->transition($request, $incidentSource, $selection, 'action', $data);
    }

    public function transfer(Request $request, IncidentSource $incidentSource, IncidentSourceSelection $selection): JsonResponse
    {
        $data = $request->validate([
            'model_type' => ['required', 'string', Rule::in(array_keys(IncidentSourceSelection::MODEL_TYPES))],
            'model_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:10000'],
        ]);

        return $this->transition($request, $incidentSource, $selection, 'transferred', $data);
    }

    private function transition(Request $request, IncidentSource $source, IncidentSourceSelection $selection, string $event, array $data = []): JsonResponse
    {
        $handling = DB::transaction(function () use ($request, $source, $selection, $event, $data) {
            $source = IncidentSource::query()->lockForUpdate()->findOrFail($source->id);
            $selection = $source->selections()->whereKey($selection->id)->firstOrFail();
            $handling = IncidentSourceHandling::query()->firstOrCreate(
                ['incident_source_selection_id' => $selection->id],
                ['incident_source_id' => $source->id, 'model_type' => $selection->model_type, 'model_id' => $selection->model_id,
                    'source_created_at' => $source->created_at, 'selection_created_at' => $selection->created_at, 'status' => 'pending']
            );
            if ($event === 'viewed' && $handling->events()->where('type', 'viewed')->exists()) {
                return $handling;
            }
            if ($event === 'started' && $handling->status !== 'pending' && $handling->status !== 'viewed') {
                throw ValidationException::withMessages(['status' => 'این رسیدگی قبلاً شروع شده یا پایان یافته است.']);
            }
            if ($event === 'action' && ($handling->status !== 'in_progress' || $handling->action()->exists())) {
                throw ValidationException::withMessages(['status' => 'برای ثبت اقدام، رسیدگی باید شروع شده و فاقد اقدام قبلی باشد.']);
            }
            if ($event === 'viewed' && $handling->status === 'pending') {
                $handling->status = 'viewed';
            } elseif ($event === 'started') {
                $handling->status = 'in_progress';
            } elseif ($event === 'action') {
                $handling->action()->create(['description' => $data['description'], 'user_id' => $request->user()->id]);
                $handling->status = 'action_taken';
            } elseif ($event === 'transferred') {
                $model = new (IncidentSourceSelection::MODEL_TYPES[$data['model_type']]);
                $destination = $model->newQuery()->find($data['model_id']);
                if (! $destination) {
                    throw ValidationException::withMessages(['model_id' => 'منشأ مقصد وجود ندارد یا حذف شده است.']);
                }
                $type = $destination->getMorphClass();
                if ($source->selections()->where('model_type', $type)->where('model_id', $destination->getKey())->exists()) {
                    throw ValidationException::withMessages(['model_id' => 'منشأ مقصد هم‌اکنون در فهرست منشأها وجود دارد.']);
                }
                $new = $source->selections()->create(['model_type' => $type, 'model_id' => $destination->getKey(), 'user_id' => $request->user()->id]);
                $data = ['from_type' => $selection->model_type, 'from_id' => $selection->model_id, 'to_type' => $type, 'to_id' => $destination->getKey(), 'to_selection_id' => $new->id, 'reason' => $data['reason']];
                $handling->status = 'transferred';
            }
            $handling->save();
            $handling->events()->create(['type' => $event, 'user_id' => $request->user()->id, 'data' => $data ?: null]);
            if ($event === 'transferred') {
                $selection->delete();
                $first = $source->selections()->first();
                $source->update(['model_type' => $first?->model_type, 'model_id' => $first?->model_id]);
                $handling->refresh();
            }

            return $handling;
        });

        return response()->json(['data' => $handling->load(['action.user:id,name', 'events.user:id,name'])]);
    }
}
