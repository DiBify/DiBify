<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 25.10.2019 13:31
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Mappers\Components\UnionRule;

class UnionMapper implements MapperInterface
{

    /** @var UnionRule[] */
    private array $rules;

    /**
     * UnionMapper constructor.
     * @param UnionRule[] $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Convert complex data (like object) to simple data (scalar, null, array)
     * @param $complex
     * @return mixed
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        foreach ($this->rules as $rule) {
            try {
                return $rule->serialize($complex);
            } catch (SerializerException $exception) {
                continue;
            }
        }

        $type = $this->getType($complex);
        throw new SerializerException("No rule for serialization '{$type}' type");
    }

    /**
     * Convert simple data (scalar, null, array) into complex data (like object)
     * @param mixed $data
     * @return mixed
     * @throws SerializerException
     */
    public function deserialize($data)
    {
        foreach ($this->rules as $rule) {
            try {
                return $rule->deserialize($data);
            } catch (SerializerException $exception) {
                continue;
            }
        }

        throw new SerializerException("No rule for deserialization");
    }

    private function getType($complex): string
    {
        if (is_object($complex)) {
            return get_class($complex);
        }
        return gettype($complex);
    }
}