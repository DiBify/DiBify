<?php


namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\UnknownModelException;
use DiBify\DiBify\Id\IdGeneratorInterface;
use DiBify\DiBify\Model\Link;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Repository\Repository;

class ConfigManager
{

    /** @var Repository[]|callable[] */
    protected $classToRepo;

    /** @var IdGeneratorInterface[] */
    protected $classToIdGenerator;

    /** @var array */
    protected $nameToClass;

    public function add($repoOrCallable, array $modelClasses, IdGeneratorInterface $idGenerator)
    {
        foreach ($modelClasses as $class) {
            /** @var ModelInterface|string $class */
            $this->classToRepo[$class] = $repoOrCallable;
            $this->classToIdGenerator[$class] = $idGenerator;
            $this->nameToClass[$class::getModelName()] = $class;
        }
    }

    /**
     * @param ModelInterface|Link|string $anyModelPointer
     * @return Repository
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getRepository($anyModelPointer): Repository
    {
        $class = $this->getClass($anyModelPointer);
        $repo = $this->classToRepo[$class];
        if ($repo instanceof Repository) {
            return $repo;
        }

        $this->classToRepo[$class] = $repo($class);
        return $this->classToRepo[$class];
    }

    /**
     * @param ModelInterface|Link|string $anyModelPointer
     * @return IdGeneratorInterface
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getIdGenerator($anyModelPointer): IdGeneratorInterface
    {
        $class = $this->getClass($anyModelPointer);
        return $this->classToIdGenerator[$class];
    }

    /**
     * @param ModelInterface|Link|string $anyModelPointer
     * @return string
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    protected function getClass($anyModelPointer): string
    {
        if ($anyModelPointer instanceof ModelInterface) {
            return get_class($anyModelPointer);
        }

        if ($anyModelPointer instanceof Link) {
            $anyModelPointer = $anyModelPointer->getModelName();
        }

        if (!is_string($anyModelPointer)) {
            throw new InvalidArgumentException('Argument should be ModelInterface, Link, model class or name');
        }

        if (isset($this->nameToClass[$anyModelPointer])) {
            return $this->nameToClass[$anyModelPointer];
        }

        if (isset($this->classToRepo[$anyModelPointer])) {
            return $anyModelPointer;
        }

        throw new UnknownModelException("No model configuration by this argument");
    }

}