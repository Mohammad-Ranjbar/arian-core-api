<?php

namespace Tests\Fixtures;

use App\Jobs\ExportJob;
use App\Models\Job;

class TimeoutExportProbeJob extends ExportJob
{
    public int $timeout = 1;

    public function handle(): void
    {
        Job::findOrFail($this->uuid)->start();
        sleep(5);
    }
}
