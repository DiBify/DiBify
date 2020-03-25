<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 15:11
 */

namespace DiBify\DiBify\Model;

use DiBify\DiBify\Id\Id;

interface ModelInterface
{

    public function id(): Id;

    public static function getModelName():string;

}