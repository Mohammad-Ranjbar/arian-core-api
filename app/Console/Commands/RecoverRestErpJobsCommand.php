<?php

namespace App\Console\Commands;

use App\Services\Erp\RestApi\RestErpJobService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RecoverRestErpJobsCommand extends Command
{
    protected $signature = 'rest-erp:recover';

    protected $description = 'Publish undispatched REST ERP jobs and flag interrupted executions without replaying ERP requests';

    public function handle(RestErpJobService $service): int
    {
        if (! Schema::hasTable('rest_erp_jobs')) {
            return self::SUCCESS;
        }
        $counts = $service->recover();
        $this->info('Published: '.$counts['published'].'; uncertain: '.$counts['uncertain'].'; publish failures: '.$counts['publish_failed']);

        return $counts['publish_failed'] ? self::FAILURE : self::SUCCESS;
    }
}
