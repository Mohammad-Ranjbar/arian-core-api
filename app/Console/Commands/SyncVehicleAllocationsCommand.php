<?php

namespace App\Console\Commands;

use App\Services\Erp\VehicleAllocationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncVehicleAllocationsCommand extends Command
{
    protected $signature = 'app:sync-vehicle-allocations';

    protected $description = 'دریافت تخصیص خودرو از OData و همگام‌سازی جدول vehicle_allocations';

    public function handle(VehicleAllocationService $service): int
    {
        try {
            $counts = $service->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('همگام‌سازی تخصیص خودرو ناموفق بود.');

            if ($exception instanceof ValidationException) {
                foreach ($exception->errors() as $messages) {
                    foreach ($messages as $message) {
                        $this->error($message);
                    }
                }
            } else {
                $this->error($exception->getMessage());
            }

            return self::FAILURE;
        }

        $this->info('همگام‌سازی تخصیص خودرو انجام شد؛ کل: '.$counts['total'].'، جدید: '.$counts['created'].'، به‌روزشده: '.$counts['updated'].'، ردشده: '.$counts['skipped']);
        if ($counts['skipped_vins'] !== []) {
            $this->warn('VINهای ردشده: '.implode('، ', $counts['skipped_vins']));
        }

        return self::SUCCESS;
    }
}
