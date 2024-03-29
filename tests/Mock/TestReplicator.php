<?php
/**
 * Created for LeadVertex
 * Date: 21.05.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Replicator\ReplicatorInterface;
use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

class TestReplicator implements ReplicatorInterface
{

    public int $onBeforeCommit = 0;
    public int $onAfterCommit = 0;

    /**
     * @var StorageInterface|null
     */
    private ?StorageInterface $primary;
    /**
     * @var StorageInterface[]
     */
    private array $secondaries;

    public function __construct(StorageInterface $primary = null, array $secondaries = [])
    {
        $this->primary = $primary;
        $this->secondaries = $secondaries;
    }

    public function getPrimary(): StorageInterface
    {
        return $this->primary;
    }

    public function getSecondaryByName(string $name): StorageInterface
    {
        return $this->primary;
    }

    public function getSecondaries(): array
    {
        return $this->secondaries;
    }

    public function insert(StorageData $data, Transaction $transaction): void
    {
        return;
    }

    public function update(StorageData $data, Transaction $transaction): void
    {
        return;
    }

    public function delete(string $id, Transaction $transaction): void
    {
        return;
    }

    public function onBeforeCommit(Transaction $transaction): void
    {
        $this->onBeforeCommit++;
    }

    public function onAfterCommit(Transaction $transaction): void
    {
        $this->onAfterCommit++;
    }

    public function freeUpMemory(): void
    {
        $this->primary->freeUpMemory();
        foreach ($this->secondaries as $secondary) {
            $secondary->freeUpMemory();
        }
    }
}