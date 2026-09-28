<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\PreGoLiveServer;
use Illuminate\Support\Facades\Validator;

readonly class SetQcStatusService
{
    public function __construct(
        private PreGoLiveServer $server
    ) {}

    public function send(
        string $vin,
        string|int $qcStatus,
        string|int $itemType,
        string|int $qcType
    ): array {
        $payload = [
            'VIN' => $vin,
            'QCStatus' => (string) $qcStatus,
            'ItemType' => (string) $itemType,
            'QCType' => (string) $qcType,
        ];

        $validator = Validator::make($payload, [
            'VIN' => [
                'required',
                'string',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/',
            ],
            'QCStatus' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
            'ItemType' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
            'QCType' => ['required', 'string', 'regex:/^[0-9]{1,10}$/'],
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => $validator->errors()->first(),
                ],
                'http_status' => 422,
            ];
        }

        return $this->server->post(
            'qcStatuses',
            $validator->validated()
        );
    }
}
