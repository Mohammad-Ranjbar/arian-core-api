<?php

namespace App\Console\Commands;

use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Services\IndexElasticSearch\BarcodeIndexService;
use Carbon\Carbon;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class IndexBarcodeCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'index:barcodes';

    /**
     * @return void
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function handle(): void
    {
        $now = Carbon::now();
        $lastIndexTime = Cache::get('INDEX:BARCODE:TIME');

        $q = Barcode::query();

        if ($lastIndexTime) {
            $q->where('updated_at', '>=', $lastIndexTime);
        }

        $items = $q->get();
        if ($items->count() == 0) {
            $this->warn('no items');
            return;
        }
        $progress = $this->output->createProgressBar($items->count());

        $progress->start();
        $items->each(function ($item) use ($progress) {
            BarcodeIndexService::index(new BarcodeResource($item));
            $progress->advance();
        });
        $progress->finish();
        Cache::put('INDEX:BARCODE:TIME', $now->toDateTimeString());
    }
}
