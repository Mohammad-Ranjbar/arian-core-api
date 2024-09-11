<?php

namespace App\Services\CustomerFinance;

class CustomerFinanceFactory
{
    public static function make($reference): bool|LamariCustomerFinanceService|PooyaCustomerFinanceService|DieselCustomerFinanceService
    {
        if ($reference === 'LamariSales') {
            return new LamariCustomerFinanceService();
        }
        if ($reference === 'PooyaSales') {
            return new PooyaCustomerFinanceService();
        }
        if ($reference === 'DieselSales') {
            return new DieselCustomerFinanceService();
        }
        return false;
    }
}
