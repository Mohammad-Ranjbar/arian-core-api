<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\PreGoLiveServer;
use Illuminate\Support\Facades\Validator;

readonly class CreateTransferZonesService
{
    public function __construct(
        private PreGoLiveServer $server
    ) {}

    public function send(
        string $itemNoArray,
        string $itemQtyArray,
        string|int $numberItems,
        string $fromLocationType,
        string $toLacationType,
        string $variantArray,
        string $crossRefArray,
        string $makeCode,
        string|int $documentNo
    ): array {
        $payload = [
            'ItemNoArray' => $itemNoArray,
            'ItemQtyArray' => $itemQtyArray,
            'NumberItems' => (string) $numberItems,
            'FromLocationType' => $fromLocationType,
            'ToLacationType' => $toLacationType,
            'VariantArray' => $variantArray,
            'CrossRefArray' => $crossRefArray,
            'MakeCode' => $makeCode,
            'DocumentNo' => (string) $documentNo,
        ];

        $validator = Validator::make($payload, [
            'ItemNoArray' => ['required', 'string'],
            'ItemQtyArray' => ['required', 'string'],
            'NumberItems' => ['required', 'string', 'regex:/^[1-9][0-9]*$/'],
            'FromLocationType' => ['required', 'string'],
            'ToLacationType' => ['required', 'string'],
            'VariantArray' => ['present', 'string'],
            'CrossRefArray' => ['present', 'string'],
            'MakeCode' => ['required', 'string'],
            'DocumentNo' => ['required', 'string'],
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
            'CreateTransferZones',
            $validator->validated()
        );
    }
}
