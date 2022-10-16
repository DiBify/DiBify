<?php
/**
 * Created for dibify
 * Date: 16.10.2022
 * @author Timur Kasumov (XAKEPEHOK)
 */
namespace DiBify\DiBify\Helpers;


use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;

class ReferenceHelper
{

    /**
     * @param ModelInterface[] $models
     * @param array|null $classes
     * @param bool $unique
     * @return Reference[]
     */
    public static function toMany(array $models, array $classes = null, bool $unique = true): array
    {
        if (!empty($classes)) {
            $models = array_filter($models, function (ModelInterface $model) use ($classes) {
                foreach ($classes as $class) {
                    if (is_a($model, $class)) {
                        return true;
                    }
                }
                return false;
            });
        }

        $links = [];
        foreach ($models as $model) {
            $link = Reference::to($model);
            $key = $unique ? spl_object_id($link) : count($links);

            if (!isset($links[$key])) {
                $links[$key] = $link;
            }
        }


        return array_values($links);
    }

}