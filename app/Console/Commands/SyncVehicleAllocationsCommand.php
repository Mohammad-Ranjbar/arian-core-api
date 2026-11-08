<?php

namespace App\Console\Commands;

use App\Services\Erp\VehicleAllocationService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

class SyncVehicleAllocationsCommand extends Command
{
    protected $signature = 'app:sync-vehicle-allocations';

    protected $description = 'Fetch vehicle allocations from ERP OData and synchronize local allocation records';

    public function handle(VehicleAllocationService $service): int
    {
        $startedAt = microtime(true);
        $counts = [];

        $this->newLine();
        $this->components->info('Vehicle Allocation Synchronization');
        $this->line('  Fetching ERP allocations, validating records, and saving local changes.');
        $this->newLine();

        try {
            $this->components->task('Synchronize vehicle allocations', function () use ($service, &$counts): bool {
                $counts = $service->sync();

                return true;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->newLine();
            $this->components->error('Vehicle allocation synchronization failed.');
            $this->line('  Error details (original diagnostic messages):');

            if ($exception instanceof ValidationException) {
                foreach ($exception->errors() as $messages) {
                    foreach ($messages as $message) {
                        $this->error(OutputFormatter::escape($message));
                    }
                }
            } else {
                $this->error(OutputFormatter::escape($exception->getMessage()));
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Allocations received', $counts['total']],
            ['Allocations created', $counts['created']],
            ['Existing allocations updated', $counts['updated']],
            ['Allocations skipped', $counts['skipped']],
        ]);
        $this->components->twoColumnDetail('Duration', number_format(microtime(true) - $startedAt, 2).' s');

        if ($counts['skipped_vins'] !== []) {
            $this->components->warn('Synchronization completed with skipped allocations. The listed vehicles could not be used.');
            $this->table(['Skipped vehicle VIN'], array_map(
                static fn (string $vin): array => [OutputFormatter::escape($vin)],
                $counts['skipped_vins'],
            ));
        } elseif ($counts['total'] === 0) {
            $this->components->info('No vehicle allocations were returned by ERP. Nothing to synchronize.');
        } else {
            $this->components->info('Vehicle allocation synchronization completed successfully.');
        }

        return self::SUCCESS;
    }
}
