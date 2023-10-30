<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdatePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-permission-command';

    /**
     * Execute the console command.
     */
    public function handle():void
    {
        $list = [];
        $routeCollection = Route::getRoutes();
        foreach ($routeCollection as $route) {
            if ($route->getName() && str_contains($route->getPrefix(), 'api')) {
                $list[] = $route->getName();
            }
        }
        try {
            /** @var Role $role */
            $role = Role::findByName('super-admin');
        } catch (Exception $exception) {
            /** @var Role $role */
            $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        }
        $progress = $this->output->createProgressBar(count($list));
        $progress->start();
        foreach ($list as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName);
            } catch (Exception $exception) {
                $permission = Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }
            $role->givePermissionTo($permission);
            $progress->advance();
        }
        $progress->finish();
    }
}
