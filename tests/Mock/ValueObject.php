<?php
/**
 * Created for dibify
 * Date: 22.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mock;


class ValueObject
{

    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}