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

class Link implements JsonSerializable
{

    /** @var Id */
    protected $id;

    /** @var string */
    protected $alias;

    /** @var ModelInterface|null */
    protected $model;

    /**
     * ModelPointer constructor.
     * @param string $modelClassOrAlias
     * @param Id|int|string $id
     */
    public function __construct(string $modelClassOrAlias, $id)
    {
        if (is_a($modelClassOrAlias, ModelInterface::class, true)) {
            /** @var ModelInterface $modelClassOrAlias */
            $this->alias = $modelClassOrAlias::getModelAlias();
        } else {
            /** @var string $modelClassOrAlias */
            $this->alias = $modelClassOrAlias;
        }

        if ($id instanceof Id) {
            $this->id = $id;
        } else {
            $this->id = new Id($id);
        }
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
        if (is_null($this->model)) {
            $this->model = ModelManager::findByLink($this);
        }
        return $this->model;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'alias' => $this->alias,
            'id' => (string) $this->id,
        ];
    }

    public static function to(ModelInterface $model): self
    {
        $link = new static($model::getModelAlias(), $model->id());
        $link->model = $model;
        return $link;
    }

    /**
     * @param string $json
     * @return Link|null
     */
    public static function fromJson(string $json): ?self
    {
        $data = json_decode($json, true);
        if (is_array($data) && isset($data['alias']) && isset($data['id'])) {
            return new static($data['alias'], new Id($data['id']));
        }
        return null;
    }
}