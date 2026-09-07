<?php

namespace App\Console\Commands;

use App\Models\CarModel;
use App\Services\Erp\ErpGetProductionLineLocation;
use Exception;
use Illuminate\Console\Command;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SetCarModelLocationCommand extends Command
{

    protected $signature = 'app:set-car-model-location-command';

    protected $description = 'Command description';

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $carModels = CarModel::query()->latest()->get();

        $this->info("Car models: {$carModels->count()}");

        $bar = $this->output->createProgressBar($carModels->count());
        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% | locations: %locations% | %message%\n");
        $bar->setMessage('-');
        $bar->setMessage('0', 'locations');
        $bar->start();

        $total = 0;
        try {
            foreach ($carModels as $carModel) {
                $bar->setMessage((string) $carModel->getAttribute('reference'));

                $items = resolve(ErpGetProductionLineLocation::class)
                    ->get($carModel->getAttribute('reference'));
                $locations = array_values(
                    array_unique(
                        array_column($items, 'location_code')
                    )
                );
                foreach ($locations as $location) {
                    $created = $carModel->locations()->firstOrCreate(
                        ['code' => $location],
                        ['title' => $location],
                    );

                    if ($created->wasRecentlyCreated) {
                        $total++;
                    }
                }

                $bar->setMessage((string) $total, 'locations');
                $bar->advance();
            }
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Assigned {$total} locations to {$carModels->count()} car models.");
    }
}
