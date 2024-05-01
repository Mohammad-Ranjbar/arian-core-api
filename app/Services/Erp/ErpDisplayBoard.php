<?php

namespace App\Services\Erp;

class ErpDisplayBoard
{

    public static function getData(): array
    {
        return ErpWsdlClient::run(config('erp.display_board'),'ProductionBoardData');
    }
}