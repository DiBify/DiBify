<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 24.03.2017 17:44
 */

namespace DiBify\DiBify\Mock;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Model\ModelInterface;

class TestModel_1 implements ModelInterface
{

    public $id;
    protected $otherId;
    protected $custom;

    public function __construct($id = null, $otherId = null, $custom = null)
    {
        $this->id = new Id($id);
        $this->otherId = new Id($otherId);
        $this->custom = $custom;
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function getOtherId()
    {
        return $this->otherId;
    }

    public function setOtherModel(TestModel_1 $model)
    {
        $this->otherId = $model->id();
    }

    public function getCustom()
    {
        return $this->custom;
    }

    public static function getModelAlias(): string
    {
        return 'model_1';
    }
}