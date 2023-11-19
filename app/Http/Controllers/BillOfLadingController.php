<?php

namespace App\Http\Controllers;

use App\Http\Resources\BillOfLadingResource;
use App\Repositories\BillOfLadingRepository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BillOfLadingController extends CrudController
{
    /**
     * @var bool
     */
    protected bool  $appendUserId    = true;
    protected array $appendStoreData = ['status' => 'new'];


    public function __construct(public readonly BillOfLadingRepository $repository)
    {

        $this->applyAuthorizeResource();
        $this->setResource(BillOfLadingResource::class);
    }


    /**
     * @throws Exception
     */
    public function afterSave($item, $data)
    {
        if ($data['type'] == 'containers') {
            $item->containers()->sync($data['items']);
            $item->containers()->update(['on_shipping' => true]);
        }
        $item->permits()->forceDelete();

        $item->car->gatePermits()->create([
            'type'       => 'enter', 'owner_id' => $item->id,
            'owner_type' => $item->getMorphClass()
        ]);

        $action = $item->wasRecentlyCreated ? 'new' : 'edit';
        $item->actionLogs()->create(['user_id' => Auth::id(), 'action' => $action]);

        return $item;
    }

    public function customStoreRules(): array
    {
        return [
            'items.*' => Rule::exists('containers', 'id')
                ->where('on_shipping', false),
        ];
    }

    public function destroy($id)
    {
        $model = $this->repository->show($id);
        if($model->status != 'new') {
            return $this->responseError('Can not delete this item',400);
        }

        $model->containers()->update(['on_shipping' => false]);
        $model->containers()->detach();
        $model->permits()->forceDelete();
        $model->actionLogs()->delete();
        return $model->forceDelete();
    }
}
