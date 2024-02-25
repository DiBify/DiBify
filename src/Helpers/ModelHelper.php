<?php
/**
 * Created for dibify
 * Date: 06.04.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Helpers;


use DiBify\DiBify\Exceptions\DuplicateModelException;
use DiBify\DiBify\Exceptions\UnassignedIdException;
use DiBify\DiBify\Model\ModelInterface;

class ModelHelper
{

    /**
     * @param ModelInterface[] $models
     * @return ModelInterface[]
     * @throws UnassignedIdException
     * @throws DuplicateModelException
     */
    public static function indexById(ModelInterface ...$models): array
    {
        $indexed = self::indexBy(function (ModelInterface $model) {
            if (!$model->id()->isAssigned()) {
                throw new UnassignedIdException('Array of models can not be indexed by non-permanent id');
            }
            return (string)$model->id();
        }, ...$models);

        if (count($models) !== count($indexed)) {
            throw new DuplicateModelException('Few models in array has same id');
        }

        return $indexed;
    }

    public static function indexBy(callable $fn, ModelInterface ...$models): array
    {
        $indexed = [];
        foreach ($models as $model) {
            $indexed[$fn($model)] = $model;
        }

        return $indexed;
    }

}