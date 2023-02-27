<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 27.02.2023 18:03
 */
namespace DiBify\DiBify\Manager;

use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class RetryPolicyTest extends TestCase
{

    private Transaction $transaction;

    private Throwable $throwable;

    private int $attempt = 1;

    private RetryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction = new Transaction();
        $this->transaction->setMetadata('retry', true);
        $this->transaction->setMetadata('init', 0);

        $this->throwable = new Exception('Message');

        $this->policy = new RetryPolicy(
            function (Transaction $transaction, Throwable $throwable, int $attempt) {
                $this->assertSame($this->transaction, $transaction);
                $this->assertSame($this->throwable, $throwable);
                $this->assertSame($this->attempt, $attempt);
                return $this->transaction->getMetadata('retry');
            },
            function (Transaction $transaction, Throwable $throwable, int $attempt) {
                $this->assertSame($this->transaction, $transaction);
                $this->assertSame($this->throwable, $throwable);
                $this->assertSame($this->attempt, $attempt);
                $this->transaction->setMetadata(
                    'init',
                    $this->transaction->getMetadata('init') + 10
                );
            },
            3,
            100000,
        );
    }

    public function testRetries(): void
    {
        $this->assertSame(3, $this->policy->retries);
    }

    public function testDelay(): void
    {
        $this->assertSame(100000, $this->policy->delay);
    }

    public function testRetry(): void
    {
        $this->assertSame(1, $this->attempt);
        $this->assertSame(0, $this->transaction->getMetadata('init'));
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(10, $this->transaction->getMetadata('init'));

        $this->attempt++;
        $this->assertSame(2, $this->attempt);
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(20, $this->transaction->getMetadata('init'));

        $this->attempt++;
        $this->transaction->setMetadata('retry', false);

        $this->assertSame(3, $this->attempt);
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(20, $this->transaction->getMetadata('init'));
    }

    public function testRetryAttempts(): void
    {
        $this->assertSame(1, $this->attempt);
        $this->assertSame(0, $this->transaction->getMetadata('init'));
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(10, $this->transaction->getMetadata('init'));

        $this->attempt++;
        $this->assertSame(2, $this->attempt);
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(20, $this->transaction->getMetadata('init'));

        $this->attempt++;
        $this->assertSame(3, $this->attempt);
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(30, $this->transaction->getMetadata('init'));

        $this->attempt++;
        $this->assertSame(4, $this->attempt);
        $this->policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertSame(30, $this->transaction->getMetadata('init'));
    }

    public function testRetryDelay(): void
    {
        $policy = new RetryPolicy(null, null, 3, 1100000);
        $time = time();
        $policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $policy->runBeforeRetry($this->transaction, $this->throwable, $this->attempt);
        $this->assertGreaterThanOrEqual(2, time() - $time);
    }
}
