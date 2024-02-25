<?php
/**
 * Created for DiBify
 * Date: 30.11.2022
 * @author Timur Kasumov (XAKEPEHOK)
 */
namespace DiBify\DiBify\Repository\Storage;


use DiBify\DiBify\Exceptions\ReplicationFailedException;

interface SecondaryStorageInterface extends StorageInterface
{

    /**
     * @param StorageData[] $insert
     * @param StorageData[] $update
     * @param StorageData[] $delete
     * @throws ReplicationFailedException
     * @return void
     */
    public function replication(array $insert, array $update, array $delete): void;

}