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
    private $isRetryRequired;

    /** @var callable */
    private $beforeRetry;

    public function __construct(
        callable   $isRetryRequiredCallable = null,
        callable   $beforeRetryCallable = null,
        public int $retries = 2,
        public int $delay = 500000,
    )
    {
        $this->isRetryRequired = $isRetryRequiredCallable ?? fn() => true;
        $this->beforeRetry = $beforeRetryCallable ?? fn() => true;
    }

    public function isRetryRequired(Transaction $transaction, Throwable $throwable, int $attempt): bool
    {
        if ($attempt > $this->retries) {
            return false;
        }

        return ($this->isRetryRequired)($transaction, $throwable, $attempt);
    }

    public function runBeforeRetry(Transaction $transaction, Throwable $throwable, int $attempt): void
    {
        if ($this->isRetryRequired($transaction, $throwable, $attempt)) {
            usleep($this->delay);
            ($this->beforeRetry)($transaction, $throwable, $attempt);
        }
    }

}