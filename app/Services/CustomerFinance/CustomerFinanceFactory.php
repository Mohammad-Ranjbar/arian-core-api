<?php

namespace App\Services\CustomerFinance;

class CustomerFinanceFactory
{
    public static function make($reference): bool|LamariCustomerFinanceService|PooyaCustomerFinanceService|DieselCustomerFinanceService|ArinaCustomerFinanceService
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
        if ($reference === 'ArinaSales') {
            return new ArinaCustomerFinanceService();
        }

        return false;
    }
}
