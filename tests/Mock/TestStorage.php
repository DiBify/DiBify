<?php
/**
 * Created for dibify
 * Date: 06.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Repository\Storage\StorageData;
use DiBify\DiBify\Repository\Storage\StorageInterface;

class TestStorage implements StorageInterface
{

    /** @var StorageData[] */
    public array $data = [];

    /**
     * @param StorageData[] $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $storageData) {
            $this->data[$storageData->id] = $storageData;
        }
    }

    public function findById(string $id): ?StorageData
    {
        return $this->data[$id] ?? null;
    }

    public function findByIds($ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (isset($this->data[$id])) {
                $result[$id] = $this->data[$id];
            }
        }
        return $result;
    }

    public function insert(StorageData $data, array $options = []): void
    {
        $this->data[$data->id] = $data;
    }

    public function update(StorageData $data, array $options = []): void
    {
        $this->data[$data->id] = $data;
    }

    public function delete(string $id, array $options = []): void
    {
        unset($this->data[$id]);
    }
}