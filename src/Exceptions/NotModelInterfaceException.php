<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 05.04.2017 2:37
 */

namespace DiBify\DiBify\Exceptions;


use Exception;

class NotModelInterfaceException extends Exception implements DiBifyExceptionInterface
{

    public function __construct($value)
    {
        if (is_object($value)) {
            $type = get_class($value);
        } else {
            $type = gettype($value);
        }

        parent::__construct("Object should be instance of ModelInterface, but '{$type}' passed", 0);
    }

}