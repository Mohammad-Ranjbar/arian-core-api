<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use TusPhp\Tus\Server as TusServer;

class TusController extends Controller
{
    public function __invoke($key)
    {
        if(!$key) {
            $key = Str::uuid();
        }
        $server = new TusServer('file');

        $server
            ->setUploadKey($key)
            ->setApiPath('/api/tus') // tus server endpoint.
            ->setUploadDir(storage_path('app/public/uploads/'))
            ->event()
            ->addListener('tus-server.upload.complete', function ($event) {

            });

        return $server->serve();

    }
}
