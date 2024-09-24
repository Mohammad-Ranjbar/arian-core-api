<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakeCrudCommand extends Command
{

    protected $signature = 'make:crud {name}';


    public function handle(): void
    {
        $name = $this->argument('name');

        Artisan::call('make:model '.$name.' -m');

        $controller = view('controller', ['name' => $name])->render();
        $resource = view('resource', ['name' => $name])->render();
        $repository = view('repository', ['name' => $name])->render();

        File::put("app/Http/Controllers/{$name}Controller.php", $controller);
        File::put("app/Repositories/{$name}Repository.php", $repository);
        File::put("app/Http/Resources/{$name}Resource.php", $resource);

        $this->info('add route to section Route::apiResources and add policy to AuthServiceProvider');
    }
}
