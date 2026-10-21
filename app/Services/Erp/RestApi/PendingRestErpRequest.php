<?php

namespace App\Services\Erp\RestApi;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Throwable;

final class PendingRestErpRequest
{
    private ?RestErpExecutionMode $executedMode = null;

    private ?array $result = null;

    private ?Throwable $failure = null;

    public function __construct(
        private readonly RestErpJobService $jobs,
        private readonly string $endpoint,
        private readonly array $parameters,
        private readonly ?Model $model,
        private readonly string $section,
        private readonly ?int $userId,
        private readonly ?int $dependsOnId = null,
    ) {}

    /**
     * @throws Throwable
     */
    public function sync(): array
    {
        return $this->execute(RestErpExecutionMode::Sync);
    }

    /**
     * @throws Throwable
     */
    public function parallel(): array
    {
        return $this->execute(RestErpExecutionMode::Parallel);
    }

    /**
     * @throws Throwable
     */
    public function unique(): array
    {
        return $this->execute(RestErpExecutionMode::Unique);
    }

    private function execute(RestErpExecutionMode $mode): array
    {
        if ($this->executedMode !== null) {
            if ($this->executedMode !== $mode) {
                throw new LogicException('این درخواست قبلاً در حالت '.$this->executedMode->value.' اجرا شده است؛ برای تغییر حالت اجرا، درخواست جدیدی آماده کنید.');
            }
            if ($this->failure !== null) {
                throw $this->failure;
            }
            if ($this->result === null) {
                throw new LogicException('این درخواست بیزینس سنترال در حال اجراست؛ تا مشخص شدن نتیجه منتظر بمانید.');
            }

            return $this->result;
        }
        $this->executedMode = $mode;

        try {
            return $this->result = $mode === RestErpExecutionMode::Sync && $this->dependsOnId === null
                ? $this->jobs->send($this->endpoint, $this->parameters, $this->model, $this->section, $this->userId)
                : $this->jobs->dispatch($this->endpoint, $this->parameters, $this->model, $this->section, $this->userId, $this->dependsOnId, mode: $mode);
        } catch (Throwable $exception) {
            // A failed dispatch can already have a persisted job; do not dispatch it twice.
            $this->failure = $exception;

            throw $exception;
        }
    }
}
