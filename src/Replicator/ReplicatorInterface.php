<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Replicator;


use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

interface ReplicatorInterface
{

    public function __construct(StorageInterface $primary, array $slaves = []);

    /**
     * Should return primary storage
     * @return StorageInterface
     */
    public function getPrimary(): StorageInterface;

    /**
     * @param string $name
     * @return StorageInterface
     */
    public function getSlaveByName(string $name): StorageInterface;

    /**
     * @return StorageInterface[]
     */
    public function getSlaves(): array;

    public function insert(StorageData $data): void;

    public function update(StorageData $data): void;

    public function delete(string $id): void;

}