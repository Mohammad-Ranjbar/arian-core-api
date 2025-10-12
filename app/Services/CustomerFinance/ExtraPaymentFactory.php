<?php

namespace App\Services\CustomerFinance;

class ExtraPaymentFactory
{
    public static function make($reference)
    {
        if ($reference === 'LamariSales') {
            return new LamariExtraPaymentService();
        }
        if ($reference === 'DieselSales') {
            return new DieselExtraPaymentService();
        }
        return false;
    }
}
