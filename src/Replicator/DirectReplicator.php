<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Replicator;


use DiBify\DiBify\Repository\Storage\StorageInterface;

class DirectReplicator implements ReplicatorInterface
{

    /**
     * @var StorageInterface[]
     */
    private $storages;

    public function __construct(array $storages)
    {
        $this->storages = $storages;
    }

    /**
     * @inheritDoc
     */
    public function getStorage(string $name = null): StorageInterface
    {
        $primary = array_key_first($this->storages);
        if (is_null($name)) {
            return $this->storages[$primary];
        }
        return $this->storages[$name];
    }

    public function insert(string $id, array $data): void
    {
        foreach ($this->storages as $storage) {
            $storage->insert($id, $data);
        }
    }

    public function update(string $id, array $data): void
    {
        foreach ($this->storages as $storage) {
            $storage->update($id, $data);
        }
    }

    public function delete(string $id): void
    {
        foreach ($this->storages as $storage) {
            $storage->delete($id);
        }
    }
}