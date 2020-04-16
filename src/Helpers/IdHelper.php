<?php


namespace DiBify\DiBify\Helpers;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;

class IdHelper
{

    /**
     * @param ModelInterface|Reference|Id|string|int $modelOrId
     * @return string|null
     */
    public static function scalarizeOne($modelOrId): ?string
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

        return $modelOrId;
    }

    /**
     * @param ModelInterface[]|Reference[]|Id[]|string[]|int[] $modelsOrIds
     * @return string[]|null[]
     */
    public static function scalarizeMany(array $modelsOrIds): array
    {
        $result = [];
        foreach ($modelsOrIds as $modelOrId) {
            $result[] = static::scalarizeOne($modelOrId);
        }
        return $result;
    }

}