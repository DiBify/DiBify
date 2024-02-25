<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Replicator;


use DiBify\DiBify\Components\FreeUpMemoryInterface;
use DiBify\DiBify\Manager\Transaction;
use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

interface ReplicatorInterface extends FreeUpMemoryInterface
{

    /**
     * Should return primary storage
     * @return StorageInterface
     */
    public function getPrimary(): StorageInterface;

    /**
     * @param string $name
     * @return StorageInterface
     */
    public function getSecondaryByName(string $name): StorageInterface;

    /**
     * @return StorageInterface[]
     */
    public function getSecondaries(): array;

    public function insert(StorageData $data, Transaction $transaction): void;

    public function update(StorageData $data, Transaction $transaction): void;

    public function delete(string $id, Transaction $transaction): void;

    public function onBeforeCommit(Transaction $transaction): void;

    public function onAfterCommit(Transaction $transaction): void;

}