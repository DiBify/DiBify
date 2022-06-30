<?php


namespace DiBify\DiBify\Helpers;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;

class IdHelper
{

    public static function scalarizeOne(ModelInterface|Reference|Id|string|int $modelOrId): ?string
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

    public static function scalarizeMany(ModelInterface|Reference|Id|string|int ...$modelsOrIds): array
    {
        $result = [];
        foreach ($modelsOrIds as $modelOrId) {
            $result[] = static::scalarizeOne($modelOrId);
        }
        return $result;
    }

}