<?php

namespace App\Services\Erp\RestApi;

use App\Exceptions\RestErpQueueException;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Services\Erp\RestApi\Servers\PreGoLiveServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;
use Throwable;

readonly class RestErpJobService
{
    public function __construct(
        private PreGoLiveServer $server,
        private RestErpLogSanitizer $sanitizer,
        private RestErpRequestContext $context
    ) {}

    public function send(string $endpoint, array $parameters, ?Model $model = null, string $section = 'rest-erp', ?int $userId = null): array
    {
        $job = $this->create($endpoint, $parameters, $model, $section, $userId, false);

        return $job->status === 'rejected' ? $job->response : $this->execute($job->getKey());
    }

    public function enqueue(string $endpoint, array $parameters, ?Model $model = null, string $section = 'rest-erp', ?int $userId = null): array
    {
        $job = $this->create($endpoint, $parameters, $model, $section, $userId, true);

        return $job->status === 'rejected' ? $job->response : [
            'success' => true,
            'data' => ['rest_erp_job_id' => $job->getKey(), 'request_id' => $job->uuid],
            'error' => null,
            'http_status' => 202,
        ];
    }

    private function create(string $endpoint, array $parameters, ?Model $model, string $section, ?int $userId, bool $queued): RestErpJob
    {
        $definition = config('rest_erp.endpoints.'.$endpoint);
        if (! is_array($definition)) {
            throw new InvalidArgumentException('Unsupported REST ERP endpoint.');
        }
        $url = $this->server->urlFor($endpoint);
        if ($model && (! $model->exists || $model->getKey() === null)) {
            throw new InvalidArgumentException('The REST ERP job must reference a saved model.');
        }
        $userId ??= Auth::id();
        if ($userId === null) {
            throw new InvalidArgumentException('An authenticated actor is required for REST ERP requests.');
        }
        $validator = Validator::make($parameters, $definition['rules']);
        $invalidEncoding = false;
        $tooLarge = false;
        try {
            $tooLarge = strlen(json_encode($parameters, JSON_THROW_ON_ERROR)) > (int) config('rest_erp.max_payload_bytes', 262144);
        } catch (JsonException) {
            $invalidEncoding = true;
        }
        $invalid = $invalidEncoding || $tooLarge || $validator->fails();
        $error = $invalid ? [
            'success' => false, 'data' => null,
            'error' => ['code' => 'VALIDATION_ERROR', 'message' => $invalidEncoding ? 'فرمت داده‌های درخواست ERP معتبر نیست.' : ($tooLarge ? 'حجم درخواست ERP بیش از حد مجاز است.' : $validator->errors()->first())],
            'http_status' => 422,
        ] : null;
        // Only declared business fields are retained for replay.
        $payload = $invalid ? [] : $validator->validated();
        $uuid = (string) Str::uuid();
        $metadata = $this->context->capture($userId);

        return DB::transaction(function () use ($endpoint, $definition, $url, $model, $section, $userId, $queued, $invalid, $error, $payload, $parameters, $uuid, $metadata) {
            $job = RestErpJob::query()->create([
                'uuid' => $uuid, 'model_type' => $model?->getMorphClass(), 'model_id' => $model?->getKey(),
                'user_id' => $userId, 'section' => $this->sanitizer->text($section, 255),
                'service' => $definition['service'], 'endpoint' => $endpoint, 'method' => 'POST', 'url' => $url,
                'server' => 'pre_go_live', 'tries' => 0, 'execute_time' => 0,
                'execution_mode' => $queued ? 'queue' : 'sync',
                'connection' => $queued ? config('rest_erp.connection', 'redis') : null,
                'queue' => $queued ? 'parallel' : null, 'status' => $invalid ? 'rejected' : 'pending',
                'parameters' => $payload,
                'payload_hash' => $this->payloadHash($uuid, $endpoint, (string) $userId, $model?->getMorphClass(), (string) $model?->getKey(), $payload),
                'request_payload' => $this->sanitizer->sanitize($parameters),
                'request_headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json', 'X-Request-ID' => $uuid],
                'response' => $error, 'http_status' => $error['http_status'] ?? null,
                'error_code' => $error['error']['code'] ?? null, 'description' => $error['error']['message'] ?? null,
                'queued_at' => $queued && ! $invalid ? now() : null, 'failed_at' => $invalid ? now() : null,
                ...$metadata,
            ]);
            $this->audit($job, 'rest_erp_created');
            if ($invalid) {
                $this->audit($job, 'rest_erp_rejected');
            } elseif ($queued) {
                $this->audit($job, 'rest_erp_queued');
                $this->publishAfterCommit($job);
            }

            return $job;
        });
    }

    private function publishAfterCommit(RestErpJob $job): void
    {
        DB::afterCommit(function () use ($job) {
            $this->publish($job->getKey());
        });
    }

    public function publish(int|string $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $job = RestErpJob::query()->lockForUpdate()->find($id);
                if (! $job || $job->status !== 'pending' || $job->execution_mode !== 'queue' || $job->dispatched_at) {
                    return false;
                }
                if (config('queue.connections.'.$job->connection.'.driver') === 'sync') {
                    throw new InvalidArgumentException('REST ERP queue execution requires an asynchronous connection.');
                }
                Bus::dispatch((new SendToRestErpJob((string) $job->getKey()))
                    ->onConnection($job->connection)->onQueue($job->queue));
                $job->update(['dispatched_at' => now()]);
                $this->audit($job, 'rest_erp_dispatched');

                return true;
            });
        } catch (Throwable $exception) {
            $this->fail($id, $exception);

            throw new RestErpQueueException($id);
        }
    }

    public function recover(): array
    {
        $counts = ['published' => 0, 'uncertain' => 0, 'publish_failed' => 0];
        RestErpJob::query()->where('status', 'pending')->where('execution_mode', 'queue')
            ->whereNull('dispatched_at')->where('queued_at', '<', now()->subMinutes(2))
            ->chunkById(100, function ($jobs) use (&$counts) {
                foreach ($jobs as $job) {
                    try {
                        $counts['published'] += (int) $this->publish($job->id);
                    } catch (Throwable) {
                        $counts['publish_failed']++;
                    }
                }
            });
        $cutoff = now()->subMinutes(5);
        RestErpJob::query()->where('status', 'in-progress')->where('started_at', '<', $cutoff)
            ->chunkById(100, function ($jobs) use (&$counts, $cutoff) {
                foreach ($jobs as $job) {
                    DB::transaction(function () use ($job, &$counts, $cutoff) {
                        $fresh = RestErpJob::query()->lockForUpdate()->find($job->id);
                        if ($fresh && $fresh->status === 'in-progress' && $fresh->started_at && $fresh->started_at->lt($cutoff)) {
                            $this->fail($fresh->id, new \RuntimeException('REST ERP worker stopped before recording a result.'));
                            $counts['uncertain']++;
                        }
                    });
                }
            });

        return $counts;
    }

    public function execute(int|string $id): ?array
    {
        $job = DB::transaction(function () use ($id) {
            $job = RestErpJob::query()->lockForUpdate()->find($id);
            if (! $job || $job->status !== 'pending') {
                return null;
            }
            $job->update([
                'status' => 'in-progress', 'tries' => $job->tries + 1, 'started_at' => now(), 'execute_time' => 0,
                'completed_at' => null, 'failed_at' => null, 'response' => null,
                'response_context' => null, 'response_headers' => null,
                'http_status' => null, 'provider_http_status' => null, 'error_code' => null, 'description' => null,
            ]);
            $this->audit($job, 'rest_erp_started', ['worker' => [
                'hostname' => gethostname() ?: null, 'pid' => getmypid(), 'php_version' => PHP_VERSION,
            ]]);

            return $job;
        });
        if (! $job) {
            return null;
        }
        $start = microtime(true);
        $responseMetadata = [];
        $sent = false;
        try {
            $payload = $job->parameters;
            $definition = config('rest_erp.endpoints.'.$job->endpoint);
            if (! is_array($definition) || $job->method !== 'POST' || $job->server !== 'pre_go_live'
                || $job->url !== $this->server->urlFor($job->endpoint)) {
                throw new InvalidArgumentException('Unsupported REST ERP operation.');
            }
            $validated = Validator::make($payload, $definition['rules'])->validate();
            if (! hash_equals($job->payload_hash, $this->payloadHash($job->uuid, $job->endpoint, (string) $job->user_id, $job->model_type, (string) $job->model_id, $validated))) {
                throw new InvalidArgumentException('REST ERP payload integrity check failed.');
            }
            $sent = true;
            $result = $this->server->post($job->endpoint, $validated, function (array $metadata) use (&$responseMetadata) {
                $responseMetadata = $metadata;
            }, $job->uuid);
        } catch (Throwable $exception) {
            $result = [
                'success' => false, 'data' => null,
                'error' => ['code' => $sent ? 'ERP_EXECUTION_UNCERTAIN' : 'ERP_JOB_ERROR', 'message' => 'اجرای درخواست REST ERP ناموفق بود.'],
                'http_status' => 500,
            ];
            $responseMetadata['exception_class'] = $exception::class;
            $responseMetadata['exception_fingerprint'] = hash('sha256', $exception->getMessage());
        }
        $code = $result['error']['code'] ?? null;
        $providerStatus = $responseMetadata['http_status'] ?? null;
        $uncertain = in_array($code, ['ERP_CONNECTION_ERROR', 'ERP_EXECUTION_UNCERTAIN', 'ERP_RESPONSE_TOO_LARGE'], true)
            || ($code === 'ERP_ERROR' && ($providerStatus === null || $providerStatus >= 500 || ($providerStatus >= 200 && $providerStatus < 300)));
        $status = $result['success'] ? 'success' : ($uncertain ? 'uncertain' : 'failed');
        DB::transaction(function () use ($job, $responseMetadata, $result, $providerStatus, $code, $status, $start) {
            $current = RestErpJob::query()->lockForUpdate()->findOrFail($job->id);
            if ($current->tries !== $job->tries || ! in_array($current->status, ['in-progress', 'uncertain'], true)) {
                $this->audit($current, 'rest_erp_late_result', [
                    'attempt' => $job->tries, 'response' => $this->sanitizer->sanitize($result),
                    'response_context' => $this->sanitizer->sanitize($responseMetadata),
                ]);

                return;
            }
            $job->update([
                'status' => $status, 'response' => $this->sanitizer->sanitize($result),
                'http_status' => $result['http_status'], 'provider_http_status' => $providerStatus,
                'response_headers' => $this->sanitizer->sanitize($responseMetadata['headers'] ?? []),
                'response_context' => $this->sanitizer->sanitize($responseMetadata),
                'error_code' => $code, 'description' => $this->sanitizer->text($result['error']['message'] ?? null),
                'execute_time' => round(microtime(true) - $start, 4),
                'completed_at' => now(), 'failed_at' => $result['success'] ? null : now(),
            ]);
            $this->audit($job, 'rest_erp_'.$status);
        });
        if (! $result['success']) {
            Log::warning('REST ERP request did not succeed', [
                'request_id' => $job->uuid, 'rest_erp_job_id' => $job->id,
                'endpoint' => $job->endpoint, 'status' => $status, 'error_code' => $code,
            ]);
        }

        return $result;
    }

    public function fail(int|string $id, Throwable $exception): void
    {
        DB::transaction(function () use ($id, $exception) {
            $job = RestErpJob::query()->lockForUpdate()->find($id);
            if (! $job || ! in_array($job->status, ['pending', 'in-progress'], true)) {
                return;
            }
            $uncertain = $job->status === 'in-progress';
            $message = $uncertain ? 'نتیجه اجرای درخواست ERP نامشخص است؛ قبل از تلاش مجدد بررسی شود.' : 'ارسال درخواست به صف ERP ناموفق بود.';
            $response = [
                'success' => false, 'data' => null,
                'error' => ['code' => $uncertain ? 'ERP_EXECUTION_UNCERTAIN' : 'ERP_QUEUE_ERROR', 'message' => $message],
                'http_status' => 503,
            ];
            $job->update([
                'status' => $uncertain ? 'uncertain' : 'failed', 'response' => $response,
                'error_code' => $response['error']['code'], 'description' => $message,
                'http_status' => 503, 'failed_at' => now(), 'completed_at' => now(),
                'execute_time' => $uncertain && $job->started_at ? round(max(0, microtime(true) - $job->started_at->getTimestamp()), 4) : 0,
                'response_context' => [
                    'exception_class' => $exception::class,
                    'exception_fingerprint' => hash('sha256', $exception->getMessage()),
                    'duration_source' => $uncertain ? 'wall_clock_estimate' : 'not_started',
                ],
            ]);
            $this->audit($job, 'rest_erp_'.$job->status, ['exception_class' => $exception::class]);
        });
    }

    public function retry(RestErpJob $job, int $userId, bool $acknowledgeUncertain = false): RestErpJob
    {
        return DB::transaction(function () use ($job, $userId, $acknowledgeUncertain) {
            $job = RestErpJob::query()->lockForUpdate()->findOrFail($job->getKey());
            if (! in_array($job->status, ['failed', 'uncertain'], true)) {
                throw ValidationException::withMessages(['status' => 'فقط درخواست ناموفق یا با نتیجه نامشخص قابل ارسال مجدد است.']);
            }
            if ($job->status === 'uncertain' && ! $acknowledgeUncertain) {
                throw ValidationException::withMessages(['acknowledge_uncertain_result' => 'ممکن است ERP درخواست را اجرا کرده باشد؛ قبل از ارسال مجدد نتیجه را بررسی و تأیید کنید.']);
            }
            $this->audit($job, 'rest_erp_retry_requested', ['actor_context' => $this->context->capture($userId)], $userId);
            $job->update([
                'status' => 'pending', 'execution_mode' => 'queue', 'connection' => config('rest_erp.connection', 'redis'),
                'queue' => 'parallel', 'queued_at' => now(), 'last_retry_at' => now(), 'last_retry_user_id' => $userId,
                'response' => null, 'response_headers' => null, 'response_context' => null,
                'http_status' => null, 'provider_http_status' => null, 'error_code' => null, 'description' => null,
                'dispatched_at' => null, 'started_at' => null, 'completed_at' => null, 'failed_at' => null, 'execute_time' => 0,
            ]);
            $this->audit($job, 'rest_erp_queued', [], $userId);
            $this->publishAfterCommit($job);

            return $job;
        });
    }

    public function ignore(RestErpJob $job, int $userId): RestErpJob
    {
        return DB::transaction(function () use ($job, $userId) {
            $job = RestErpJob::query()->lockForUpdate()->findOrFail($job->getKey());
            if (! in_array($job->status, ['failed', 'uncertain', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'درخواست در این وضعیت قابل نادیده گرفتن نیست.']);
            }
            $job->update(['status' => 'ignored', 'ignored_at' => now()]);
            $this->audit($job, 'rest_erp_ignored', ['actor_context' => $this->context->capture($userId)], $userId);

            return $job;
        });
    }

    private function audit(RestErpJob $job, string $action, array $extra = [], ?int $actorId = null): void
    {
        $actorId ??= $job->user_id;
        DB::table('rest_erp_job_actors')->insertOrIgnore(['rest_erp_job_id' => $job->getKey(), 'user_id' => $actorId]);
        $snapshot = $job->attributesToArray();
        $parameters = $snapshot['parameters'] ?? null;
        unset($snapshot['parameters']);
        $data = $this->sanitizer->sanitize([
            'result' => [
                'status' => $job->status, 'tries' => $job->tries,
                'http_status' => $job->http_status, 'provider_http_status' => $job->provider_http_status,
                'error_code' => $job->error_code, 'description' => $job->description,
                'execute_time' => $job->execute_time,
            ],
            ...$extra, 'snapshot' => $snapshot,
        ]);
        if (! is_array($data)) {
            $data = ['_truncated' => true];
        }
        if (! is_array($data['snapshot'] ?? null)) {
            $data['snapshot'] = ['id' => $job->id, 'uuid' => $job->uuid, '_truncated' => true];
        }
        // Validated business parameters are size-limited at creation and must remain exact in the audit.
        // Authentication headers/credentials are never part of these endpoint payloads.
        $data['snapshot']['parameters'] = $parameters;
        $job->actionLogs()->create([
            'user_id' => $actorId, 'action' => $action, 'data' => $data,
        ]);
    }

    private function payloadHash(string $uuid, string $endpoint, string $userId, ?string $modelType, string $modelId, array $payload): string
    {
        // Bind the payload to this job and its original actor/model to detect tampering.
        $identity = implode("\0", ['rest-erp-payload-v1', $uuid, $endpoint, $userId, $modelType ?? '', $modelId]);

        return hash_hmac('sha256', $identity."\0".json_encode($payload, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
