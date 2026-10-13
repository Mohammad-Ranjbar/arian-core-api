<?php

use App\Services\Erp\RestApi\ApproveExitZonesService;
use App\Services\Erp\RestApi\ApproveReturnZonesService;
use App\Services\Erp\RestApi\CreateTransferZonesService;
use App\Services\Erp\RestApi\ItemReclassJournalsService;
use App\Services\Erp\RestApi\SetQcStatusService;

return [
    'base_url' => env('ERP_REST_BASE_URL', 'http://172.16.0.154:7048/PreBC/api/arianCapital/product/v1.0'),
    'allowed_hosts' => ['172.16.0.154'],
    'connection' => env('REST_ERP_QUEUE_CONNECTION', 'redis'),
    'queue' => 'parallel',
    'max_payload_bytes' => 262144,
    'max_response_bytes' => 1048576,
    'log_string_limit' => 4096,
    'log_item_limit' => 500,
    'log_depth_limit' => 8,
    'log_byte_limit' => 65536,
    'endpoints' => [
        'ApproveExitZones' => [
            'service' => ApproveExitZonesService::class,
            'rules' => [
                'BarcodeInfo' => ['required', 'string'],
                'ItemType' => ['required', 'string'],
            ],
        ],
        'ApproveReturnZones' => [
            'service' => ApproveReturnZonesService::class,
            'rules' => [
                'VIN' => ['required', 'string'],
                'ItemType' => ['required', 'string'],
            ],
        ],
        'ItemReclassJournals' => [
            'service' => ItemReclassJournalsService::class,
            'rules' => [
                'ArrayCrossReferenceNo' => ['required', 'string'],
                'BatchNo' => ['required', 'string'],
                'Number' => ['required', 'string', 'regex:/^[1-9][0-9]*$/'],
                'BatchName' => ['required', 'string'],
            ],
        ],
        'qcStatuses' => [
            'service' => SetQcStatusService::class,
            'rules' => [
                'VIN' => ['required', 'string', 'regex:/^[A-HJ-NPR-Z0-9]{17}$/'],
                'QCStatus' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
                'ItemType' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
                'QCType' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
            ],
        ],
        'CreateTransferZones' => [
            'service' => CreateTransferZonesService::class,
            'rules' => [
                'ItemNoArray' => ['required', 'string'],
                'ItemQtyArray' => ['required', 'string'],
                'NumberItems' => ['required', 'string', 'regex:/^[1-9][0-9]*$/'],
                'FromLocationType' => ['required', 'string'],
                'ToLacationType' => ['required', 'string'],
                'VariantArray' => ['present', 'string'],
                'CrossRefArray' => ['present', 'string'],
                'MakeCode' => ['required', 'string'],
                'DocumentNo' => ['required', 'string'],
            ],
        ],
    ],
];
