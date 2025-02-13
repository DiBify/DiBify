<?php


namespace DiBify\DiBify\Helpers;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Repository\Storage\StorageData;

class IdHelper
{

    public static function scalarizeOne(ModelInterface|Reference|Id|StorageData|string|int $modelOrId): ?string
    {
        if ($modelOrId instanceof ModelInterface) {
            return (string) $modelOrId->id();
        }

        if ($modelOrId instanceof Reference) {
            return (string) $modelOrId->id();
        }

        if ($modelOrId instanceof Id) {
            return (string) $modelOrId;
        }

        if ($modelOrId instanceof StorageData) {
            return (string) $modelOrId->id;
        }

        return $modelOrId;
    }

    public static function scalarizeMany(ModelInterface|Reference|Id|StorageData|string|int ...$modelsOrIds): array
    {
        $result = [];
        foreach ($modelsOrIds as $modelOrId) {
            $result[] = static::scalarizeOne($modelOrId);
        }
        return $result;
    }

}