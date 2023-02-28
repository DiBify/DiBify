<?php


namespace DiBify\DiBify\Manager;


use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Exceptions\UnknownModelException;
use DiBify\DiBify\Id\IdGeneratorInterface;
use DiBify\DiBify\Model\Reference;
use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Repository\Repository;

class ConfigManager
{

    /** @var Repository[]|callable[] */
    protected array $classToRepo;

    /** @var IdGeneratorInterface[] */
    protected array $classToIdGenerator;

    protected array $aliasToClass;

    public function add(Repository|callable $repoOrCallable, array $modelClasses, IdGeneratorInterface $idGenerator)
    {
        foreach ($modelClasses as $class) {
            /** @var ModelInterface|string $class */
            $this->classToRepo[$class] = $repoOrCallable;
            $this->classToIdGenerator[$class] = $idGenerator;
            $this->aliasToClass[$class::getModelAlias()] = $class;
        }
    }

    /**
     * @internal
     * @return array
     */
    public function getModelClasses(): array
    {
        return array_keys($this->classToRepo);
    }

    /**
     * @internal
     * @param ModelInterface|Reference|string $anyModelPointer
     * @return Repository
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getRepository(ModelInterface|Reference|string $anyModelPointer): Repository
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
     * @internal
     * @param ModelInterface|Reference|string $anyModelPointer
     * @return IdGeneratorInterface
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    public function getIdGenerator(ModelInterface|Reference|string $anyModelPointer): IdGeneratorInterface
    {
        $class = $this->getClass($anyModelPointer);
        return $this->classToIdGenerator[$class];
    }

    /**
     * @param ModelInterface|Reference|string $anyModelPointer
     * @return string
     * @throws InvalidArgumentException
     * @throws UnknownModelException
     */
    protected function getClass(ModelInterface|Reference|string $anyModelPointer): string
    {
        if ($anyModelPointer instanceof ModelInterface) {
            return get_class($anyModelPointer);
        }

        if ($anyModelPointer instanceof Reference) {
            $anyModelPointer = $anyModelPointer->getModelAlias();
        }

        if (!is_string($anyModelPointer)) {
            throw new InvalidArgumentException('Argument should be ModelInterface, Reference, model class or alias');
        }

        if (isset($this->aliasToClass[$anyModelPointer])) {
            return $this->aliasToClass[$anyModelPointer];
        }

        if (isset($this->classToRepo[$anyModelPointer])) {
            return $anyModelPointer;
        }

        throw new UnknownModelException("No model configuration by this argument");
    }

}