<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Replicator;


use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

class DirectReplicator implements ReplicatorInterface
{

    private StorageInterface $primary;

    /** @var StorageInterface[] */
    private array $slaves;

    public function __construct(StorageInterface $primary, array $slaves = [])
    {
        $this->primary = $primary;
        $this->slaves = $slaves;
    }

    public function getPrimary(): StorageInterface
    {
        return $this->primary;
    }

    /**
     * @inheritDoc
     */
    public function getSlaveByName(string $name): StorageInterface
    {
        return $this->slaves[$name];
    }

    public function getSlaves(): array
    {
        return $this->slaves;
    }

    public function insert(StorageData $data, Transaction $transaction): void
    {
        $this->primary->insert($data);
        foreach ($this->slaves as $slave) {
            $slave->insert($data);
        }
    }

    public function update(StorageData $data, Transaction $transaction): void
    {
        $this->primary->update($data);
        foreach ($this->slaves as $slave) {
            $slave->update($data);
        }
    }

    public function delete(string $id, Transaction $transaction): void
    {
        $this->primary->delete($id);
        foreach ($this->slaves as $slave) {
            $slave->delete($id);
        }
    }

    public function onBeforeCommit(): void
    {
        return;
    }

    public function onAfterCommit(): void
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