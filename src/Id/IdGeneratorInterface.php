<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 15.03.2017 16:25
 */

namespace DiBify\DiBify\Id;

use DiBify\DiBify\Model\ModelInterface;

interface IdGeneratorInterface
{

    public function __invoke(ModelInterface $model): Id;

}