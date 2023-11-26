<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TusPhp\Tus\Server as TusServer;

class TusServiceProvider extends ServiceProvider
{

    public function register(): void
    {

        $this->app->singleton('tus-server', function ($app) {
            $server = new TusServer('redis');

            $server
                ->setApiPath('/tus') // tus server endpoint.
                ->setUploadDir(storage_path('app/public/uploads')); // uploads dir.

            return $server;
        });


    }


    public function boot(): void
    {
        //
    }
}
