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

    /**
     * @var StorageInterface|null
     */
    private ?StorageInterface $primary;
    /**
     * @var StorageInterface[]
     */
    private array $slaves;

    public function __construct(StorageInterface $primary = null, array $slaves = [])
    {
        $this->primary = $primary;
        $this->slaves = $slaves;
    }

    public function getPrimary(): StorageInterface
    {
        return $this->primary;
    }

    public function getSlaveByName(string $name): StorageInterface
    {
        return $this->primary;
    }

    public function getSlaves(): array
    {
        return $this->slaves;
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

    public function freeUpMemory(): void
    {
        $this->primary->freeUpMemory();
        foreach ($this->slaves as $slave) {
            $slave->freeUpMemory();
        }
    }
}