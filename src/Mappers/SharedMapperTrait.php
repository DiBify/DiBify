<?php
/**
 * Created for dibify
 * Date: 19.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


trait SharedMapperTrait
{
    private static $instance;

    public static function getInstance(): self
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

}