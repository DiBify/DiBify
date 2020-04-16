<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:05
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Model;


use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Manager\ModelManager;
use JsonSerializable;

final class Reference implements JsonSerializable
{

    /** @var Id */
    private $id;

    /** @var string */
    private $alias;

    /** @var ModelInterface|null */
    private $model;

    /** @var self[] */
    private static $preload = [];

    /** @var self[] */
    private static $references = [];

    /**
     * ModelPointer constructor.
     * @param string $alias
     * @param Id $id
     */
    private function __construct(string $alias, Id $id)
    {
        $this->alias = $alias;
        $this->id = $id;
    }

    /**
     * @return Id
     */
    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getModelAlias(): string
    {
        return $this->alias;
    }

    public function isFor(ModelInterface $model): bool
    {
        return $this->getModelAlias() === $model::getModelAlias() && $this->id->isEqual($model);
    }

    public function getModel(): ?ModelInterface
    {
        if ($this->model) {
            return $this->model;
        }

        self::preload($this);

        /** @var Reference[] $references */
        $references = array_filter(self::$preload, function (Reference $reference) {
            return $reference->model === null;
        });

        $assoc = ModelManager::findByReferences($references);
        foreach ($assoc as $reference => $model) {
            /** @var Reference $reference */
            $reference->model = $model;
        }

        self::$preload = [];

        return $this->model;
    }

    /**
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by json_encode
     */
    public function jsonSerialize()
    {
        return [
            'alias' => $this->alias,
            'id' => (string) $this->id,
        ];
    }

    /**
     * @param string $modelClassOrAlias
     * @param Id|string|int $id
     * @return static
     */
    public static function create(string $modelClassOrAlias, $id): self
    {
        if (is_a($modelClassOrAlias, ModelInterface::class, true)) {
            /** @var ModelInterface $modelClassOrAlias */
            $alias = $modelClassOrAlias::getModelAlias();
        } else {
            $alias = $modelClassOrAlias;
        }

        if ($id instanceof Id) {
            $hash = "{{" . spl_object_hash($id) . "}}";

            if (!$id->isAssigned()) {
                if (!isset(self::$references[$alias][$hash])) {
                    self::$references[$alias][$hash] = new self($alias, $id);
                }
            }

            if (isset(self::$references[$alias][$hash])) {
                return self::$references[$alias][$hash];
            }

        } else {
            $id = new Id($id);
        }


        $scalarId = (string) $id;
        if (!isset(self::$references[$alias][$scalarId])) {
            self::$references[$alias][$scalarId] = new self($alias, $id);
        }

        return self::$references[$alias][$scalarId];
    }

    public static function to(ModelInterface $model): self
    {
        $reference = self::create($model::getModelAlias(), $model->id());
        $reference->model = $model;
        return $reference;
    }

    public static function preload(Reference $reference): void
    {
        self::$preload[] = $reference;
    }

    public static function freeUpMemory(): void
    {
        self::$preload = [];
        self::$references = [];
    }

    /**
     * @param string $json
     * @return Reference|null
     */
    public static function fromJson(string $json): ?self
    {
        $data = json_decode($json, true);
        if (is_array($data) && isset($data['alias']) && isset($data['id'])) {
            return self::create($data['alias'], $data['id']);
        }
        return null;
    }
}