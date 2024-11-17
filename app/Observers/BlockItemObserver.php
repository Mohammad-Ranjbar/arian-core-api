<?php

namespace App\Observers;

use App\Events\ErpJobEvent;
use App\Models\BlockItem;
use App\Models\ErpJob;
use App\Repositories\BlockReasonRepository;
use Illuminate\Support\Facades\Auth;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BlockItemObserver
{

  public function __construct(public BlockReasonRepository $reasonRepository)
  {

  }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function created(BlockItem $blockItem): void
    {
        $blockReason = $this->reasonRepository->findBy(request()->get('block_reason_id'),'id');

        $parameters = [
            'barcodeInfoP' =>$blockItem->getAttribute('vin'),
            'blockTypeP' => $blockReason->getAttribute('block_type'),
        ];
        $erpJob = ErpJob::query()->create(
            [
                'queue' => ErpJob::QUEUE_UNIQUE,
                'user_id' => $blockItem->getAttribute('blocked_by'),
                'method' => 'BlockingVehicleByType',
                'url' => config('erp.block_unblock'),
                'parameters' => $parameters,
                'model_type' => BlockItem::class,
                'model_id' => $blockItem->getAttribute('id'),
                'section' => ErpJob::BLOCK_ITEM,
            ]
        );
        ErpJobEvent::dispatch($erpJob);
    }


    public function updated(BlockItem $blockItem): void
    {
        //
    }


    public function deleted(BlockItem $blockItem): void
    {
        //
    }


    public function restored(BlockItem $blockItem): void
    {
        //
    }

}
