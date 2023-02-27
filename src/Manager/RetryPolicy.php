<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 27.02.2023 18:02
 */
namespace DiBify\DiBify\Manager;

use Throwable;

class RetryPolicy
{

    /** @var callable */
    private $isRetryRequiredCallable;

    /** @var callable */
    private $beforeRetryCallable;

    public function __construct(
        callable   $isRetryRequiredCallable = null,
        callable   $beforeRetryCallable = null,
        public int $retries = 2,
        public int $delay = 500000,
    )
    {
        $this->isRetryRequiredCallable = $isRetryRequiredCallable ?? fn() => true;
        $this->beforeRetryCallable = $beforeRetryCallable ?? fn() => true;
    }

    public function runBeforeRetry(Transaction $transaction, Throwable $throwable, int $attempt): bool
    {
        if ($this->isRetryRequired($transaction, $throwable, $attempt)) {
            usleep($this->delay);
            ($this->beforeRetryCallable)($transaction, $throwable, $attempt);
            return true;
        }
        return false;
    }

    private function isRetryRequired(Transaction $transaction, Throwable $throwable, int $attempt): bool
    {
        if ($attempt > $this->retries) {
            return false;
        }

        return ($this->isRetryRequiredCallable)($transaction, $throwable, $attempt);
    }

}