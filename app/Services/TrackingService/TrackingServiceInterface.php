<?php

namespace App\Services\TrackingService;

interface TrackingServiceInterface
{
    /**
     * @param $data
     * @return array|false
     */
    public function enter($data): array|false;


    /**
     * @param $data
     * @return array|false
     */
    public function exit($data): array|false;


    /**
     * @param $data
     * @return array
     */
    public function prepareData($data): array;
}