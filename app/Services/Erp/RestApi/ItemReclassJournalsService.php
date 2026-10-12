<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;

readonly class ItemReclassJournalsService
{
    public function __construct(private RestErpJobService $jobs) {}

    public function send(
        string $arrayCrossReferenceNo,
        string $batchNo,
        string|int $number,
        string $batchName,
        ?Model $model = null
    ): array {
        return $this->jobs->send('ItemReclassJournals', $this->payload($arrayCrossReferenceNo, $batchNo, $number, $batchName), $model, 'send-basket');
    }

    public function queue(
        string $arrayCrossReferenceNo,
        string $batchNo,
        string|int $number,
        string $batchName,
        ?Model $model = null,
        ?int $userId = null
    ): array {
        return $this->jobs->enqueue('ItemReclassJournals', $this->payload($arrayCrossReferenceNo, $batchNo, $number, $batchName), $model, 'send-basket', $userId);
    }

    private function payload(
        string $arrayCrossReferenceNo,
        string $batchNo,
        string|int $number,
        string $batchName
    ): array {
        return [
            'ArrayCrossReferenceNo' => $arrayCrossReferenceNo,
            'BatchNo' => $batchNo,
            'Number' => (string) $number,
            'BatchName' => $batchName,
        ];
    }
}
