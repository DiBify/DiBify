<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2017 13:45
 */

namespace DiBify\DiBify\Id;


use DiBify\DiBify\Model\ModelInterface;
use JsonSerializable;

class Id implements JsonSerializable
{

    /** @var string|null */
    private $value;

    /**
     * Id constructor.
     * @param string $value
     */
    public function __construct(string $value = null)
    {
        if (is_null($value) === false) {
            $this->assign($value);
        }
    }

    public function get(): ?string
    {
        return $this->value;
    }

    public function isAssigned(): bool
    {
        return $this->value !== null;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function assign(string $id): bool
    {
        if (!$this->isAssigned()) {
            $this->value = $id;
            return true;
        }
        return false;
    }

    /**
     * @param Id | ModelInterface | int | string $modelOrId
     * @return bool
     */
    public function isEqual($modelOrId): bool
    {
        if ($modelOrId instanceof self) {
            return (string) $modelOrId === (string) $this;
        }

        if ($modelOrId instanceof ModelInterface) {
            /** @var Id $id */
            $id = $modelOrId->id();
            return (string) $id === (string) $this;
        }

        if (is_scalar($modelOrId)) {
            return $modelOrId == (string) $this;
        }

        return false;
    }

    public function __toString()
    {
        return (string) $this->value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}