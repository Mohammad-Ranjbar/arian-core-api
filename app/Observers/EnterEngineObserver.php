<?php

namespace App\Observers;

use App\Models\EnterEngine;

class EnterEngineObserver
{

    public function created(EnterEngine $enterEngine): void
    {
        if ($enterEngine->getAttribute('production_line_id')) {
            $enterEngine->update(['counter' => EnterEngine::computeSynchronousCounter($enterEngine->getAttribute('production_line_id'))]);
        }
    }


    public function updated(EnterEngine $enterEngine): void
    {
        //
    }

    public function deleted(EnterEngine $enterEngine): void
    {
        //
    }

    public function restored(EnterEngine $enterEngine): void
    {
        //
    }


    public function forceDeleted(EnterEngine $enterEngine): void
    {
        //
    }
}
