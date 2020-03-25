<?php


namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Exceptions\NotModelInterfaceException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Id\UuidGenerator;
use DiBify\DiBify\Model\ModelInterface;

class Commit
{
    /**
     * @var Id $id
     */
    private $id;
    /**
     * @var ModelInterface[]
     */
    private $persisted;
    /**
     * @var ModelInterface[]
     */
    private $deleted;

    /**
     * Commit constructor.
     * @param ModelInterface[] $persisted
     * @param ModelInterface[] $deleted
     * @throws NotModelInterfaceException
     */
    public function __construct(array $persisted, array $deleted)
    {
        $this->id = new Id(UuidGenerator::generate());
        $this->guardNotModelInterface($persisted);
        $this->guardNotModelInterface($deleted);

        $this->persisted = array_values($persisted);
        $this->deleted = array_values($deleted);
    }

    /**
     * @return Id
     */
    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @param string|null $modelClass
     * @return ModelInterface[]
     */
    public function getPersisted(string $modelClass = null): array
    {
        return $this->filter($this->persisted, $modelClass);
    }

    /**
     * @param string|null $modelClass
     * @return ModelInterface[]
     */
    public function getDeleted(string $modelClass = null): array
    {
        return $this->filter($this->deleted, $modelClass);
    }

    /**
     * @param ModelInterface[] $models
     * @param string $modelClass
     * @return ModelInterface[]
     */
    private function filter(array $models, string $modelClass = null): array
    {
        if ($modelClass === null) {
            return $models;
        }

        return array_filter($models, function (ModelInterface $model) use ($modelClass) {
            return get_class($model) === $modelClass;
        });
    }

    /**
     * @param array $models
     * @throws NotModelInterfaceException
     */
    private function guardNotModelInterface(array $models)
    {
        foreach ($models as $model) {
            if (!($model instanceof ModelInterface)) {
                throw new NotModelInterfaceException($model);
            }
        }
    }

}