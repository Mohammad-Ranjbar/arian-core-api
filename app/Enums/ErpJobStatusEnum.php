<?php


namespace App\Enums;


enum ProductStatusEnum: string
{
    case Start = 'start';
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}
