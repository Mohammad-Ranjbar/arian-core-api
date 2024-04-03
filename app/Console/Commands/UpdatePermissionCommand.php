<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class UpdatePermissionCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'permission:update';

    /**
     * @return void
     */
    public function handle(): void
    {
        $list = collect([
            'vehicles.faults.qc.index',
            'vehicles.faults.evaluation.index',
            'vehicles.faults.qc.submit',
            'vehicles.faults.evaluation.submit',
            'vehicles.faults.qc.report',
            'vehicles.faults.evaluation.report',

            'bill-of-lading.change-status.temporary_receipt',
            'bill-of-lading.change-status.enter',
            'bill-of-lading.change-status.discharge',

            'production-tracking.salons',
            'production-tracking.stations',
            'production-tracking.production_lines',
            'production-tracking.sale_locations',
            'production-tracking.inventories',
            'production-tracking.locations',

            'vehicle.print',
            'vehicle.print.qr',
            'vehicle.print.barcode',
            'vehicle.print.information',
        ]);
        $routeCollection = Route::getRoutes();
        foreach ($routeCollection as $route) {
            if ($route->getName() && str_contains($route->getPrefix(), 'api')) {
                $list->push($route->getName());
            }
        }

        $oldList = array_diff(Permission::query()->get()->pluck('name')->all(), $list->all());
        if (!empty($oldList)) {
            $progressDelete = $this->output->createProgressBar(count($oldList));
            $progressDelete->setBarCharacter('-');
            $progressDelete->start();
            foreach ($oldList as $old) {
                Permission::query()->where('name', $old)->delete();
                $progressDelete->advance();
            }
            $progressDelete->finish();
        }
        $newList = array_diff($list->all(), Permission::query()->get()->pluck('name')->all());

        if (empty($newList)) {
            $this->info('There in no permission to insert!');
            return;
        }

        /** @var Role $role */
        $role = Role::query()
            ->where('name', 'super-admin')
            ->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->output->writeln('');
        $progress = $this->output->createProgressBar(count($newList));
        $progress->setBarCharacter('+');
        $progress->start();
        foreach ($newList as $permissionName) {
            $permission = Permission::query()->create(
                [
                    'name'       => $permissionName,
                    'guard_name' => 'web'
                ]
            );
            $role->permissions()->attach($permission);
            $progress->advance();
        }
        $progress->finish();
    }
}
