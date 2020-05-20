<?php
/**
 * Created for LeadVertex
 * Date: 21.05.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Replicator\ReplicatorInterface;
use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

class TestReplicator implements ReplicatorInterface
{

    /**
     * @var StorageInterface|null
     */
    private $primary;
    /**
     * @var array
     */
    private $slaves;

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

    public function insert(StorageData $data): void
    {
        return;
    }

    public function update(StorageData $data): void
    {
        return;
    }

    public function delete(string $id): void
    {
        return;
    }
}