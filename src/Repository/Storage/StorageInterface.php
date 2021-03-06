<?php
/**
 * Created for DiBify
 * Date: 08.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Storage;


interface StorageInterface
{

    /**
     * @param int|string $id
     * @return array|null
     */
    public function findById(string $id): ?StorageData;

    /**
     * @param string[] $ids
     * @return StorageData[]
     */
    public function findByIds($ids): array;

    public function insert(StorageData $data): void;

    public function update(StorageData $data): void;

    public function delete(string $id): void;

}