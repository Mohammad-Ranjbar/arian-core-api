<?php

namespace App\Services\CustomerFinance;

class CustomerFinanceFactory
{
    public static function make($reference): bool|LamariCustomerFinanceService|PooyaCustomerFinanceService
    {
        if ($reference === 'LamariSales') {
            return new LamariCustomerFinanceService();
        }
        if ($reference === 'PooyaSales') {
            return new PooyaCustomerFinanceService();
        }
        return false;
    }
}
