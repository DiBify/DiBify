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
    private array $secondaries;

    public function __construct(StorageInterface $primary, array $secondaries = [])
    {
        $this->primary = $primary;
        $this->secondaries = $secondaries;
    }

    public function getPrimary(): StorageInterface
    {
        return $this->primary;
    }

    /**
     * @inheritDoc
     */
    public function getSecondaryByName(string $name): StorageInterface
    {
        return $this->secondaries[$name];
    }

    public function getSecondaries(): array
    {
        return $this->secondaries;
    }

    public function insert(StorageData $data, Transaction $transaction): void
    {
        $this->primary->insert($data);
        foreach ($this->secondaries as $slave) {
            $slave->insert($data);
        }
    }

    public function update(StorageData $data, Transaction $transaction): void
    {
        $this->primary->update($data);
        foreach ($this->secondaries as $slave) {
            $slave->update($data);
        }
    }

    public function delete(string $id, Transaction $transaction): void
    {
        $this->primary->delete($id);
        foreach ($this->secondaries as $slave) {
            $slave->delete($id);
        }
    }

    public function onBeforeCommit(Transaction $transaction): void
    {
        return;
    }

    public function onAfterCommit(Transaction $transaction): void
    {
        return;
    }

    public function freeUpMemory(): void
    {
        $this->primary->freeUpMemory();
        foreach ($this->secondaries as $secondary) {
            $secondary->freeUpMemory();
        }
    }
}