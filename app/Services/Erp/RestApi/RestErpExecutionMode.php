<?php

namespace App\Services\Erp\RestApi;

enum RestErpExecutionMode: string
{
    case Sync = 'sync';
    case Parallel = 'parallel';
    case Unique = 'unique';
}
