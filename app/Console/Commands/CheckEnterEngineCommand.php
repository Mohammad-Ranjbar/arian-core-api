<?php

namespace App\Console\Commands;

use App\Models\EnterEngine;
use Illuminate\Console\Command;

class CheckEnterEngineCommand extends Command
{

    protected $signature = 'app:check-enter-engine-command';


    protected $description = 'Command description';

    public function handle(): void
    {
        $data = EnterEngine::query()->whereNull('make_code_id')->get();

        foreach ($data as $item) {
            $item->update([
                'make_code_id' => 2,
                'counter' => $item->id
            ]);

        }
    }
}
