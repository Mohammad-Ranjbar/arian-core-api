<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFromSqlServerCommand extends Command
{

    protected $signature = 'app:sync-from-sql-server-command';


    protected $description = 'Command description';

    public function handle():void
    {
        $connection = DB::connection('sqlsrv');

        $result = $connection->select('SELECT * FROM your_table_name');
        dd($result);

// Fetch the results
        $result = $result->get();
    }
}
