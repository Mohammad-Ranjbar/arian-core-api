<?php

namespace App\Services\Erp\RestApi;

use App\Events\TrackingEvent;
use App\Exceptions\RestErpQueueException;
use App\Http\Resources\TrackingResource;
use App\Jobs\SendToRestErpJob;
use App\Models\RestErpJob;
use App\Models\Tracking;
use App\Services\Erp\RestApi\Servers\Server;
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
    private const VALIDATION_MESSAGES = [
        'required' => 'وارد کردن :attribute الزامی است.',
        'present' => 'فیلد :attribute باید در درخواست وجود داشته باشد.',
        'string' => 'مقدار :attribute باید از نوع متن باشد.',
        'regex' => 'قالب مقدار :attribute معتبر نیست.',
    ];

    private const VALIDATION_ATTRIBUTES = [
        'WCGroup' => 'گروه مرکز کاری', 'WithTracking' => 'ثبت رهگیری', 'WithOutput' => 'ثبت خروجی',
        'VIN' => 'شماره شاسی', 'VINArray' => 'فهرست شماره شاسی‌ها',
        'BarcodeInfo' => 'بارکد خودرو', 'BarCode' => 'بارکد قطعه',
        'Destination' => 'مقصد', 'ItemType' => 'نوع کالا', 'ItemTypeP' => 'نوع کالا',
        'TypeOfExit' => 'نوع خروج', 'NumberVIN' => 'تعداد خودروها',
        'PDIResult' => 'نتیجه بازرسی پیش از تحویل',
        'PDIDescription' => 'توضیحات بازرسی پیش از تحویل',
        'PDIResultDesc' => 'توضیحات تکمیلی بازرسی پیش از تحویل',
        'EngineNo' => 'شماره موتور', 'ModelYear' => 'سال مدل خودرو',
        'PoliceInspectionDate' => 'تاریخ بازدید پلیس',
        'QCStatus' => 'وضعیت کنترل کیفیت', 'QCType' => 'نوع کنترل کیفیت',
        'BlockType' => 'نوع مسدودی', 'ArrayCrossReferenceNo' => 'فهرست کدهای مرجع قطعات',
        'BatchNo' => 'شماره دسته', 'BatchName' => 'نام دسته', 'Number' => 'تعداد اقلام',
        'ItemNoArray' => 'فهرست کدهای قطعات', 'ItemQtyArray' => 'فهرست تعداد قطعات',
        'NumberItems' => 'تعداد اقلام', 'FromLocationType' => 'نوع محل مبدأ',
        'ToLacationType' => 'نوع محل مقصد', 'ToLocationType' => 'نوع محل مقصد', 'VariantArray' => 'فهرست گونه‌های قطعات',
        'CrossRefArray' => 'فهرست کدهای مرجع', 'MakeCode' => 'کد سازنده', 'DocumentNo' => 'شماره سند',
    ];

    public function __construct(
        private Server $server,
        private RestErpLogSanitizer $sanitizer,
        private RestErpRequestContext $context
    ) {}

    public function pending(string $endpoint, array $parameters, ?Model $model = null, string $section = 'ارتباط با ERP', ?int $userId = null, ?int $dependsOnId = null): PendingRestErpRequest
    {
        return new PendingRestErpRequest($this, $endpoint, $parameters, $model, $section, $userId, $dependsOnId);
    }

    public function send(string $endpoint, array $parameters, ?Model $model = null, string $section = 'ارتباط با ERP', ?int $userId = null, bool $reuseExistingJob = false): array
    {
        $job = $this->register($endpoint, $parameters, $model, $section, $userId, false, reuseExistingJob: $reuseExistingJob);

        if (is_array($job)) {
            return $job;
        }

        $result = $job->status === 'rejected' ? $this->rejectedResult($job) : $this->execute($job->getKey());

        return $reuseExistingJob ? $this->storedResult($job->refresh()) : $result;
    }

    public function enqueue(string $endpoint, array $parameters, ?Model $model = null, string $section = 'ارتباط با ERP', ?int $userId = null, ?int $dependsOnId = null): array
    {
        $job = $this->register($endpoint, $parameters, $model, $section, $userId, true, $dependsOnId);
        if (is_array($job)) {
            return $job;
        }

        return $job->status === 'rejected' ? $this->rejectedResult($job) : [
            'success' => true,
            'data' => ['rest_erp_job_id' => $job->getKey(), 'request_id' => $job->uuid],
            'error' => null,
            'http_status' => 202,
        ];
    }

    public function dispatch(string $endpoint, array $parameters, ?Model $model = null, string $section = 'ارتباط با ERP', ?int $userId = null, ?int $dependsOnId = null, ?RestErpExecutionMode $mode = null): array
    {
        $mode ??= $this->configuredMode($endpoint);
        if ($mode === RestErpExecutionMode::Sync && $dependsOnId !== null
            && ! RestErpJob::query()->whereKey($dependsOnId)->where('status', 'success')->exists()) {
            throw new InvalidArgumentException('برای اجرای فوری این درخواست، ابتدا باید درخواست پیش‌نیاز آن با موفقیت تکمیل شود.');
        }
        $job = $this->register($endpoint, $parameters, $model, $section, $userId,
            $mode !== RestErpExecutionMode::Sync, $dependsOnId, queue: $mode === RestErpExecutionMode::Unique ? 'unique' : 'parallel');
        if (is_array($job)) {
            return $job;
        }
        if ($job->status === 'rejected') {
            return $this->rejectedResult($job);
        }
        if ($mode === RestErpExecutionMode::Sync) {
            $this->execute($job->getKey());

            return $this->storedResult($job->refresh());
        }

        return [
            'success' => true,
            'data' => ['rest_erp_job_id' => $job->id, 'request_id' => $job->uuid],
            'error' => null, 'http_status' => 202,
        ];
    }

    private function configuredMode(string $endpoint): RestErpExecutionMode
    {
        $value = config('rest_erp.endpoints.'.$endpoint.'.mode', 'parallel');
        $mode = is_string($value) ? RestErpExecutionMode::tryFrom($value) : null;
        if ($mode === null) {
            throw new InvalidArgumentException('حالت اجرای عملیات '.$endpoint.' معتبر نیست؛ یکی از حالت‌های sync، parallel یا unique را انتخاب کنید.');
        }

        return $mode;
    }

    private function rejectedResult(RestErpJob $job): array
    {
        return [
            'success' => false, 'data' => null,
            'error' => ['code' => $job->error_code, 'message' => $job->description],
            'http_status' => $job->http_status,
        ];
    }

    private function register(string $endpoint, array $parameters, ?Model $model, string $section, ?int $userId, bool $queued, ?int $dependsOnId = null, bool $reuseExistingJob = false, string $queue = 'parallel'): RestErpJob|array
    {
        $definition = config('rest_erp.endpoints.'.$endpoint);
        if (! is_array($definition)) {
            throw new InvalidArgumentException('عملیات درخواستی بیزینس سنترال پشتیبانی نمی‌شود.');
        }
        $url = $this->server->urlFor($endpoint);
        if ($model && (! $model->exists || $model->getKey() === null)) {
            throw new InvalidArgumentException('رکورد مرتبط با درخواست بیزینس سنترال باید پیش از ارسال در سامانه ثبت شده باشد.');
        }
        $userId ??= Auth::id();
        if ($userId === null) {
            throw new InvalidArgumentException('برای ارسال درخواست به بیزینس سنترال، کاربر اقدام‌کننده باید مشخص و احراز هویت شده باشد.');
        }
        if ($dependsOnId !== null && ! RestErpJob::query()->whereKey($dependsOnId)->where('user_id', $userId)->exists()) {
            throw new InvalidArgumentException('درخواست پیش‌نیاز باید متعلق به همان کاربر ارسال‌کننده باشد.');
        }
        $validator = Validator::make($parameters, $definition['rules'], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);
        $invalidEncoding = false;
        $encodingError = null;
        $tooLarge = false;
        $payloadBytes = 0;
        try {
            $payloadBytes = strlen(json_encode($parameters, JSON_THROW_ON_ERROR));
            $tooLarge = $payloadBytes > (int) config('rest_erp.max_payload_bytes', 262144);
        } catch (JsonException $exception) {
            $invalidEncoding = true;
            $encodingError = 'داده‌های درخواست قابل تبدیل به JSON نیستند؛ ساختار داده‌ها و کدگذاری UTF-8 را بررسی کنید.';
        }
        $invalid = $invalidEncoding || $tooLarge || $validator->fails();
        $error = $invalid ? [
            'success' => false, 'data' => null,
            'error' => ['code' => 'VALIDATION_ERROR', 'message' => $this->sanitizer->errorText($invalidEncoding ? $encodingError : ($tooLarge ? 'حجم داده‌های درخواست ('.$payloadBytes.' بایت) از حد مجاز '.config('rest_erp.max_payload_bytes', 262144).' بایت بیشتر است.' : implode('; ', $validator->errors()->all())))],
            'http_status' => 422,
        ] : null;
        // Only declared business fields are retained for replay.
        $payload = $invalid ? [] : $validator->validated();
        $uuid = (string) Str::uuid();
        $metadata = $this->context->capture($userId);

        return DB::transaction(function () use ($endpoint, $definition, $url, $model, $section, $userId, $queued, $invalid, $error, $payload, $parameters, $uuid, $metadata, $dependsOnId, $reuseExistingJob, $queue) {
            // Serialize registration even on the first request, before any job exists.
            if ($reuseExistingJob) {
                DB::table('users')->where('id', $userId)->lockForUpdate()->first();
                $query = RestErpJob::query()->where('user_id', $userId)->where('endpoint', $endpoint);
                $safeParameters = $this->sanitizer->sanitize($parameters);
                if (is_array($safeParameters)) {
                    foreach ($safeParameters as $key => $value) {
                        $query->where('request_payload->'.$key, $value);
                    }
                }
                // Logged values may be redacted; compare the actual validated payload as well.
                $job = $query->latest('id')->lockForUpdate()->get()->first(fn ($candidate) => $invalid
                    ? $candidate->request_payload === $safeParameters
                    : $candidate->parameters === $payload);
                if ($job) {
                    if (! $invalid && in_array($job->status, ['failed', 'uncertain'], true)) {
                        // An explicit resubmission retries the same operation synchronously.
                        $this->prepareRetry($job, $userId, true);

                        return $job;
                    }

                    return $this->storedResult($job);
                }
            } elseif ($model !== null) {
                DB::table($model->getTable())->where($model->getKeyName(), $model->getKey())->lockForUpdate()->first();
                $job = RestErpJob::query()->where('model_type', $model->getMorphClass())
                    ->where('model_id', $model->getKey())->where('endpoint', $endpoint)->latest('id')->lockForUpdate()->first();
            } elseif (! $queued) {
                // Unlinked requests have no entity identity; retain the limited same-actor retry behavior.
                DB::table('users')->where('id', $userId)->lockForUpdate()->first();
                $job = RestErpJob::query()->whereNull('model_type')->whereNull('model_id')
                    ->where('user_id', $userId)->where('endpoint', $endpoint)
                    ->where('section', $this->sanitizer->text($section, 255))->latest('id')->lockForUpdate()->first();
                if ($job && ($job->execution_mode !== 'sync' || $job->parameters !== $parameters
                    || ! in_array($job->status, ['failed', 'pending', 'in-progress', 'uncertain'], true))) {
                    $job = null;
                }
            } else {
                $job = null;
            }
            if ($job && in_array($job->status, ['pending', 'in-progress', 'uncertain'], true)) {
                if ($queued && ! $invalid && $job->status === 'pending' && $job->execution_mode === 'queue'
                    && $job->parameters === $payload && $job->depends_on_id === $dependsOnId) {
                    return $job;
                }

                return $this->blockedResult($job);
            }
            if ($job && $dependsOnId === $job->getKey()) {
                throw new InvalidArgumentException('درخواست بیزینس سنترال نمی‌تواند پیش‌نیاز خودش باشد.');
            }
            $attributes = [
                'uuid' => $uuid, 'model_type' => $model?->getMorphClass(), 'model_id' => $model?->getKey(),
                'user_id' => $userId, 'section' => $this->sanitizer->text($section, 255),
                'depends_on_id' => $dependsOnId,
                'service' => $definition['service'], 'endpoint' => $endpoint, 'method' => 'POST', 'url' => $url,
                'server' => 'pre_go_live', 'tries' => 0, 'execute_time' => 0,
                'execution_mode' => $queued ? 'queue' : 'sync',
                'connection' => $queued ? config('rest_erp.connection', 'redis') : null,
                'queue' => $queued ? $queue : null, 'status' => $invalid ? 'rejected' : 'pending',
                'parameters' => $payload,
                'payload_hash' => $this->payloadHash($uuid, $endpoint, (string) $userId, $model?->getMorphClass(), (string) $model?->getKey(), $payload, $dependsOnId),
                'request_payload' => $this->sanitizer->sanitize($parameters),
                'http_status' => $error['http_status'] ?? null,
                'error_code' => $error['error']['code'] ?? null, 'description' => $error['error']['message'] ?? null,
                'queued_at' => $queued && ! $invalid ? now() : null, 'failed_at' => $invalid ? now() : null,
                ...$metadata,
            ];
            if ($job) {
                // The UUID and original actor remain bound to the same job; prior ERP outcomes retain their snapshots.
                unset($attributes['uuid'], $attributes['user_id'], $attributes['tries']);
                $attributes['payload_hash'] = $this->payloadHash($job->uuid, $endpoint, (string) $job->user_id,
                    $job->model_type, (string) $job->model_id, $payload, $dependsOnId);
                $this->prepareRetry($job, $userId, ! $queued, $attributes);
            } else {
                $job = RestErpJob::query()->create($attributes);
                $this->audit($job, 'rest_erp_created');
                if (! $invalid) {
                    $this->recordProductionTracking($job);
                }
            }
            if ($queued && ! $invalid) {
                $this->publishAfterCommit($job);
            }

            return $job;
        });
    }

    private function storedResult(RestErpJob $job): array
    {
        $pending = in_array($job->status, ['pending', 'in-progress'], true);
        $success = $job->status === 'success';

        return [
            'success' => $success,
            'data' => $success ? ['rest_erp_job_id' => $job->id, 'request_id' => $job->uuid] : null,
            'error' => $success ? null : [
                'code' => $pending ? 'ERP_JOB_IN_PROGRESS' : ($job->error_code ?? 'ERP_JOB_NOT_SUCCESSFUL'),
                'message' => $pending ? 'درخواست قبلی بیزینس سنترال هنوز در حال پردازش است.' : ($job->description ?? 'درخواست قبلی بیزینس سنترال موفق نبوده است.'),
            ],
            'http_status' => $pending ? 409 : ($job->http_status ?? 409),
            'rest_erp_job_id' => $job->id,
        ];
    }

    private function blockedResult(RestErpJob $job): array
    {
        return [
            'success' => false, 'data' => null,
            'error' => [
                'code' => $job->status === 'uncertain' ? 'ERP_EXECUTION_UNCERTAIN' : 'ERP_JOB_IN_PROGRESS',
                'message' => $job->status === 'uncertain'
                    ? 'نتیجه درخواست قبلی بیزینس سنترال نامشخص است؛ ابتدا نتیجه عملیات را بررسی و سپس ارسال مجدد همان درخواست را تأیید کنید.'
                    : 'درخواست قبلی بیزینس سنترال هنوز در حال پردازش است.',
            ],
            'rest_erp_job_id' => $job->getKey(), 'http_status' => 409,
        ];
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
                if (! $this->dependencySucceeded($job)) {
                    return false;
                }
                if (config('queue.connections.'.$job->connection.'.driver') === 'sync') {
                    throw new InvalidArgumentException('برای ارسال درخواست به صف، باید یک اتصال صف غیرهم‌زمان مانند Redis تنظیم شود.');
                }
                Bus::dispatch((new SendToRestErpJob((string) $job->getKey()))
                    ->onConnection($job->connection)->onQueue($job->queue));
                $job->update(['dispatched_at' => now()]);

                return true;
            });
        } catch (Throwable $exception) {
            $this->fail($id, $exception);

            throw new RestErpQueueException($id, $this->sanitizer->exceptionText($exception));
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
        $cutoff = now()->subMinutes(6);
        RestErpJob::query()->where('status', 'in-progress')->where('started_at', '<', $cutoff)
            ->chunkById(100, function ($jobs) use (&$counts, $cutoff) {
                foreach ($jobs as $job) {
                    DB::transaction(function () use ($job, &$counts, $cutoff) {
                        $fresh = RestErpJob::query()->lockForUpdate()->find($job->id);
                        if ($fresh && $fresh->status === 'in-progress' && $fresh->started_at && $fresh->started_at->lt($cutoff)) {
                            $this->fail($fresh->id, new \RuntimeException('پردازشگر صف پیش از ثبت نتیجه درخواست بیزینس سنترال متوقف شد؛ نتیجه عملیات باید بررسی شود.'));
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
            if (! $job || $job->status !== 'pending' || ! $this->dependencySucceeded($job)) {
                return null;
            }
            $job->update([
                'status' => 'in-progress', 'tries' => $job->tries + 1, 'started_at' => now(), 'execute_time' => 0,
                'completed_at' => null, 'failed_at' => null,
                'http_status' => null, 'provider_http_status' => null, 'error_code' => null, 'description' => null,
            ]);

            return $job;
        });
        if (! $job) {
            return null;
        }
        $start = microtime(true);
        $providerStatus = null;
        $providerBody = null;
        $sent = false;
        $exceptionContext = [];
        try {
            $payload = $job->parameters;
            $definition = config('rest_erp.endpoints.'.$job->endpoint);
            if (! is_array($definition) || $job->method !== 'POST' || $job->server !== 'pre_go_live'
                || $job->url !== $this->server->urlFor($job->endpoint)) {
                throw new InvalidArgumentException('مشخصات عملیات بیزینس سنترال معتبر نیست یا این عملیات پشتیبانی نمی‌شود.');
            }
            $validated = Validator::make($payload, $definition['rules'], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES)->validate();
            if (! hash_equals($job->payload_hash, $this->payloadHash($job->uuid, $job->endpoint, (string) $job->user_id, $job->model_type, (string) $job->model_id, $validated, $job->depends_on_id))) {
                throw new InvalidArgumentException('داده‌های درخواست بیزینس سنترال پس از ثبت تغییر کرده‌اند؛ ارسال درخواست متوقف شد.');
            }
            $sent = true;
            $result = $this->server->post($job->endpoint, $validated, function (array $metadata) use (&$providerStatus, &$providerBody) {
                $providerStatus = $metadata['http_status'];
                $providerBody = $metadata['body'];
            }, $job->uuid);
        } catch (Throwable $exception) {
            $exceptionContext = [
                'exception_class' => $exception::class, 'stage' => $sent ? 'request' : 'preflight',
            ];
            $message = $this->sanitizer->exceptionText($exception);
            $result = [
                'success' => false, 'data' => null,
                'error' => ['code' => $sent ? 'ERP_EXECUTION_UNCERTAIN' : 'ERP_JOB_ERROR', 'message' => $message],
                'http_status' => 500,
            ];
        }
        $code = $result['error']['code'] ?? null;
        $uncertain = in_array($code, ['ERP_CONNECTION_ERROR', 'ERP_EXECUTION_UNCERTAIN', 'ERP_RESPONSE_TOO_LARGE'], true)
            || ($code === 'ERP_ERROR' && $providerStatus === null)
            || (! $result['success'] && $code !== 'Application_DialogException' && $providerStatus !== null
                && ($providerStatus >= 500 || ($providerStatus >= 200 && $providerStatus < 300)));
        $status = $result['success'] ? 'success' : ($uncertain ? 'uncertain' : 'failed');
        DB::transaction(function () use ($job, $result, $providerStatus, $providerBody, $code, $status, $start, $sent) {
            $current = RestErpJob::query()->lockForUpdate()->findOrFail($job->id);
            if ($current->tries !== $job->tries || ! in_array($current->status, ['in-progress', 'uncertain'], true)) {
                return;
            }
            $job->update([
                'status' => $status,
                'http_status' => $result['http_status'], 'provider_http_status' => $providerStatus,
                'error_code' => $code, 'description' => $this->sanitizer->text($result['error']['message'] ?? null),
                'execute_time' => round(microtime(true) - $start, 4),
                'completed_at' => now(), 'failed_at' => $result['success'] ? null : now(),
            ]);
            if ($sent) {
                $this->audit($job, 'rest_erp_'.$status);
            }
            if ($providerStatus !== null && $job->endpoint === 'ProductionPostingBINs') {
                $this->recordProductionTracking($job, $this->productionTrackingResponse($result, $providerBody), broadcast: true);
            }
        });
        if (! $result['success']) {
            $logMessage = $this->sanitizer->errorText($result['error']['message'] ?? 'درخواست بیزینس سنترال با موفقیت انجام نشد.');
            Log::warning($logMessage, [
                'request_id' => $job->uuid, 'rest_erp_job_id' => $job->id,
                'endpoint' => $job->endpoint, 'status' => $status, 'error_code' => $code,
                ...$exceptionContext,
            ]);
        } else {
            RestErpJob::query()->where('depends_on_id', $job->id)->where('status', 'pending')
                ->where('execution_mode', 'queue')->whereNull('dispatched_at')
                ->chunkById(100, function ($jobs) {
                    foreach ($jobs as $dependent) {
                        try {
                            $this->publish($dependent->id);
                        } catch (RestErpQueueException) {
                            // The child records its broker failure; the successful parent must not be replayed.
                            Log::warning('ارسال درخواست وابسته بیزینس سنترال به صف ناموفق بود.', ['rest_erp_job_id' => $dependent->id]);
                        }
                    }
                });
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
            $message = $this->sanitizer->exceptionText($exception);
            $job->update([
                'status' => $uncertain ? 'uncertain' : 'failed',
                'error_code' => $uncertain ? 'ERP_EXECUTION_UNCERTAIN' : 'ERP_QUEUE_ERROR', 'description' => $message,
                'http_status' => 503, 'failed_at' => now(), 'completed_at' => now(),
                'execute_time' => $uncertain && $job->started_at ? round(max(0, microtime(true) - $job->started_at->getTimestamp()), 4) : 0,
            ]);
            Log::warning($message, [
                'request_id' => $job->uuid, 'rest_erp_job_id' => $job->id,
                'endpoint' => $job->endpoint, 'status' => $job->status, 'error_code' => $job->error_code,
            ]);
        });
    }

    private function productionTrackingResponse(array $result, mixed $providerBody): array
    {
        if (! $result['success']) {
            return ['status' => 'error', 'message' => $this->sanitizer->errorText($result['error']['message'])];
        }

        $message = is_string($providerBody) ? $providerBody : null;
        if (is_array($providerBody)) {
            foreach (['message', 'value'] as $key) {
                if (isset($providerBody[$key]) && is_string($providerBody[$key])) {
                    $message = $providerBody[$key];
                    break;
                }
            }
        }
        // Preserve an unfamiliar provider response instead of inventing a success message.
        $message ??= $providerBody === [] || $providerBody === null ? ''
            : json_encode($providerBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return ['status' => 'ok', 'message' => $this->sanitizer->errorText($message)];
    }

    private function recordProductionTracking(RestErpJob $job, mixed $response = null, bool $broadcast = false): void
    {
        if ($job->endpoint !== 'ProductionPostingBINs' || $job->model_type !== (new Tracking)->getMorphClass()) {
            return;
        }
        $tracking = Tracking::query()->lockForUpdate()->find($job->model_id);
        if (! $tracking) {
            return;
        }
        // Keep this field empty until an actual ERP HTTP response is received.
        $tracking->update(['data' => $response]);

        if ($broadcast) {
            DB::afterCommit(function () use ($tracking, $job) {
                try {
                    TrackingEvent::dispatch(new TrackingResource($tracking->fresh()));
                } catch (Throwable $exception) {
                    // A notification failure must never cause an ERP posting to be replayed.
                    Log::warning('ارسال اعلان نتیجه رهگیری تولید ناموفق بود.', [
                        'rest_erp_job_id' => $job->id, 'exception_class' => $exception::class,
                    ]);
                }
            });
        }
    }

    public function retry(RestErpJob $job, int $userId, bool $acknowledgeUncertain = false): RestErpJob
    {
        $retried = DB::transaction(function () use ($job, $userId, $acknowledgeUncertain) {
            $job = RestErpJob::query()->lockForUpdate()->findOrFail($job->getKey());
            if (! in_array($job->status, ['failed', 'uncertain'], true)) {
                throw ValidationException::withMessages(['status' => 'فقط درخواست ناموفق یا با نتیجه نامشخص قابل ارسال مجدد است.']);
            }
            if ($job->status === 'uncertain' && ! $acknowledgeUncertain) {
                throw ValidationException::withMessages(['acknowledge_uncertain_result' => 'ممکن است بیزینس سنترال درخواست را اجرا کرده باشد؛ پیش از ارسال مجدد، نتیجه عملیات را بررسی و تأیید کنید.']);
            }
            $sync = $job->execution_mode === 'sync';
            $this->prepareRetry($job, $userId, $sync);
            if ($sync) {
                DB::afterCommit(fn () => $this->execute($job->getKey()));
            } else {
                $this->publishAfterCommit($job);
            }

            return $job;
        });

        return $retried->refresh();
    }

    private function prepareRetry(RestErpJob $job, int $userId, bool $sync, array $attributes = []): void
    {
        $this->recordActor($job, $userId);
        $job->update([
            'status' => 'pending', 'execution_mode' => $sync ? 'sync' : 'queue',
            'connection' => $sync ? null : ($job->connection ?? config('rest_erp.connection', 'redis')),
            'queue' => $sync ? null : ($job->queue ?? 'parallel'), 'queued_at' => $sync ? null : now(), 'last_retry_at' => now(), 'last_retry_user_id' => $userId,
            'http_status' => null, 'provider_http_status' => null, 'error_code' => null, 'description' => null,
            'dispatched_at' => null, 'started_at' => null, 'completed_at' => null, 'failed_at' => null, 'execute_time' => 0,
            'ignored_at' => null,
            ...$attributes,
        ]);
        $this->recordProductionTracking($job);
    }

    public function ignore(RestErpJob $job, int $userId): RestErpJob
    {
        return DB::transaction(function () use ($job, $userId) {
            $job = RestErpJob::query()->lockForUpdate()->findOrFail($job->getKey());
            if (! in_array($job->status, ['failed', 'uncertain', 'rejected'], true)) {
                throw ValidationException::withMessages(['status' => 'درخواست در این وضعیت قابل نادیده گرفتن نیست.']);
            }
            $job->update(['status' => 'ignored', 'ignored_at' => now()]);
            $this->recordActor($job, $userId);

            return $job;
        });
    }

    private function audit(RestErpJob $job, string $action): void
    {
        $actorId = $job->user_id;
        $this->recordActor($job, $actorId);
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
            'snapshot' => $snapshot,
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

    private function recordActor(RestErpJob $job, int $actorId): void
    {
        DB::table('rest_erp_job_actors')->insertOrIgnore(['rest_erp_job_id' => $job->getKey(), 'user_id' => $actorId]);
    }

    private function dependencySucceeded(RestErpJob $job): bool
    {
        return $job->depends_on_id === null
            || RestErpJob::query()->whereKey($job->depends_on_id)->where('status', 'success')->exists();
    }

    private function payloadHash(string $uuid, string $endpoint, string $userId, ?string $modelType, string $modelId, array $payload, ?int $dependsOnId = null): string
    {
        // Bind the payload to this job and its original actor/model to detect tampering.
        $identity = implode("\0", ['rest-erp-payload-v1', $uuid, $endpoint, $userId, $modelType ?? '', $modelId]);
        if ($dependsOnId !== null) {
            $identity .= "\0dependency\0".$dependsOnId;
        }

        return hash_hmac('sha256', $identity."\0".json_encode($payload, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
