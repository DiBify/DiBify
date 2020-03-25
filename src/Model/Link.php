<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:05
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Model;


use DiBify\DiBify\Id\Id;
use JsonSerializable;

class Link implements JsonSerializable
{

    /** @var Id */
    protected $id;

    /** @var string */
    protected $model;

    /**
     * ModelPointer constructor.
     * @param string $modelClassOrName
     * @param Id|int|string $id
     */
    public function __construct(string $modelClassOrName, $id)
    {
        if (is_a($modelClassOrName, ModelInterface::class, true)) {
            /** @var ModelInterface $modelClassOrName */
            $this->model = $modelClassOrName::getModelName();
        } else {
            /** @var string $modelClassOrName */
            $this->model = $modelClassOrName;
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
    public function getModelName(): string
    {
        return $this->model;
    }

    public function isFor(ModelInterface $model): bool
    {
        return $this->getModelName() === $model::getModelName() && $this->id->isEqual($model);
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
            'model' => $this->model,
            'id' => (string) $this->id,
        ];
    }

    public static function to(ModelInterface $model): self
    {
        return new static($model::getModelName(), $model->id());
    }

    /**
     * @param string $json
     * @return Link|null
     */
    public static function fromJson(string $json): ?self
    {
        $data = json_decode($json, true);
        if (is_array($data) && isset($data['model']) && isset($data['id'])) {
            return new static($data['model'], new Id($data['id']));
        }
        return null;
    }
}