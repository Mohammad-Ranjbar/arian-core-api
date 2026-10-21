<?php

namespace App\Services\Erp\RestApi;

use App\Services\Erp\RestApi\Servers\Server;

readonly class ProductionBoardEntriesService
{
    public function __construct(private Server $server) {}

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
                    'message' => 'ساختار پاسخ تابلوی تولید بیزینس سنترال معتبر نیست؛ فیلد value باید فهرستی از رکوردها باشد.',
                ],
                'http_status' => 502,
            ];
        }

        $result['data'] = array_map(fn (array $entry) => (object) $entry, $entries);

        return $result;
    }
}
