<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\PreGoLiveServer;
use Illuminate\Support\Facades\Validator;

readonly class ItemReclassJournalsService
{
    public function __construct(
        private PreGoLiveServer $server
    ) {}

    public function send(
        string $arrayCrossReferenceNo,
        string $batchNo,
        string|int $number,
        string $batchName
    ): array {
        $payload = [
            'ArrayCrossReferenceNo' => $arrayCrossReferenceNo,
            'BatchNo' => $batchNo,
            'Number' => (string) $number,
            'BatchName' => $batchName,
        ];

        $validator = Validator::make($payload, [
            'ArrayCrossReferenceNo' => ['required', 'string'],
            'BatchNo' => ['required', 'string'],
            'Number' => ['required', 'string', 'regex:/^[1-9][0-9]*$/'],
            'BatchName' => ['required', 'string'],
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
            'ItemReclassJournals',
            $validator->validated()
        );
    }
}
