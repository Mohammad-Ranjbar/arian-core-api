<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\PreGoLiveServer;

readonly class ProductionBoardEntriesService
{
    public function __construct(private PreGoLiveServer $server) {}

    public function get(): array
    {
        $result = $this->server->get('productionBoardEntries');

        if (! $result['success']) {
            return $result;
        }

        $entries = $result['data']['value'] ?? null;
        if (! is_array($entries) || ! array_is_list($entries)
            || collect($entries)->contains(fn ($entry) => ! is_array($entry))) {
            return [
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => 'ERP_INVALID_RESPONSE',
                    'message' => 'ERP productionBoardEntries response must contain a value array of entries.',
                ],
                'http_status' => 502,
            ];
        }

        $result['data'] = array_map(fn (array $entry) => (object) $entry, $entries);

        return $result;
    }
}
