<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2017 13:45
 */

namespace DiBify\DiBify\Id;


use DiBify\DiBify\Model\ModelInterface;
use DiBify\DiBify\Model\Reference;
use JsonSerializable;

class Id implements JsonSerializable
{

    private ?string $value = null;

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
        if ($id === '') {
            return false;
        }

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
    public function isEqual(ModelInterface|Reference|Id|string|int $modelOrId): bool
    {
        if ($modelOrId instanceof self) {
            if ($this->isAssigned()) {
                return (string) $modelOrId === (string) $this;
            } else {
                return $modelOrId === $this;
            }
        }

        if ($modelOrId instanceof ModelInterface || $modelOrId instanceof Reference) {
            /** @var Id $id */
            $id = $modelOrId->id();
            if ($id->isAssigned()) {
                return (string) $id === (string) $this;
            } else {
                return $id === $this;
            }
        }

        if (is_scalar($modelOrId)) {
            return $modelOrId == (string) $this;
        }

        return false;
    }

    public function __toString(): string
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