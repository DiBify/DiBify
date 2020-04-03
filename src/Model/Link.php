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

final class Link implements JsonSerializable
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
    private static $links = [];

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

        /** @var Link[] $links */
        $links = array_filter(self::$preload, function (Link $link) {
            return $link->model === null;
        });

        $assoc = ModelManager::findByLinks($links);
        foreach ($assoc as $link => $model) {
            /** @var Link $link */
            $link->model = $model;
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
                if (!isset(self::$links[$alias][$hash])) {
                    self::$links[$alias][$hash] = new self($alias, $id);
                }
            }

            if (isset(self::$links[$alias][$hash])) {
                return self::$links[$alias][$hash];
            }

        } else {
            $id = new Id($id);
        }


        $scalarId = (string) $id;
        if (!isset(self::$links[$alias][$scalarId])) {
            self::$links[$alias][$scalarId] = new self($alias, $id);
        }

        return self::$links[$alias][$scalarId];
    }

    public static function to(ModelInterface $model): self
    {
        $link = self::create($model::getModelAlias(), $model->id());
        $link->model = $model;
        return $link;
    }

    public static function preload(Link $link): void
    {
        self::$preload[] = $link;
    }

    public static function freeUpMemory(): void
    {
        self::$preload = [];
        self::$links = [];
    }

    /**
     * @param string $json
     * @return Link|null
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