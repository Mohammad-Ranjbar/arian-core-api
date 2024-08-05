<?php

namespace App\Services\CustomerFinance;

class CustomerFinanceFactory
{
    public static function make($reference): bool|LamariCustomerFinanceService
    {
        if ($reference === 'LamariSales') {
            return new LamariCustomerFinanceService();
        }
        return false;
    }
}
