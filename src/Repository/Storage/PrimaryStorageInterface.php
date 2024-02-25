<?php
/**
 * Created for DiBify
 * Date: 30.11.2022
 * @author Timur Kasumov (XAKEPEHOK)
 */
namespace DiBify\DiBify\Repository\Storage;

use DateTimeImmutable;
use Generator;

interface PrimaryStorageInterface extends StorageInterface
{

    /**
     * @param StorageData ...$array
     * @return StorageData[]
     */
    public function findForReplication(StorageData ...$array): array;

    public function findForReplicationSync(DateTimeImmutable $gte, int $batchSize): Generator;

    public function findCountForReplicationSync(DateTimeImmutable $gte): int;

    public function getVersionField(): string;

}