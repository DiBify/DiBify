<?php
/**
 * Created for DiBify.
 * Datetime: 12.12.2018 17:00
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Exceptions\InvalidArgumentException;
use DiBify\DiBify\Id\Id;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class DeepIdentityMapper
 * @package DiBify\DiBify\Mappers
 *
 * This mapper can deserialize and serialize any types of data by Reflection. All that you need - pass unique string
 * identities for class names, than can be hydrated and extracted. This mapper support multi level nested arrays
 * and objects, that can be fully serialized
 */
class DeepIdentityMapper implements MapperInterface
{
    /**
     * @var array
     */
    private $classMap;
    /**
     * @var array
     */
    private $identityMap;
    /**
     * @var int
     */
    private $reflectionProperties;
    /**
     * @var ReflectionClass[]
     */
    private $reflectionClasses = [];
    /**
     * @var string
     */
    private $identityKey;

    /**
     * DeepIdentityMapper constructor.
     * @param array $classMap
     * @param string $identityKey
     * @param array $reflectionProperties
     * @throws InvalidArgumentException
     */
    public function __construct(
        array $classMap,
        string $identityKey = '___identity___',
        array $reflectionProperties = [
            ReflectionProperty::IS_PUBLIC,
            ReflectionProperty::IS_PROTECTED,
            ReflectionProperty::IS_PRIVATE,
        ]
    )
    {
        foreach ($classMap as $className => $callableOrIdentity) {
            $value = is_callable($callableOrIdentity) ? $callableOrIdentity($className) : $callableOrIdentity;
            $this->classMap[$className] = $value;
        }

        if (!isset($this->classMap[Id::class])) {
            $this->classMap[Id::class] = 'id';
        }

        $this->identityMap = array_flip($this->classMap);

        if (count($this->classMap) != count($this->identityMap)) {
            throw new InvalidArgumentException('Mismatch count of $classMap and $identityMap');
        }

        $this->reflectionProperties =  array_reduce($reflectionProperties, function($a, $b) { return $a | $b; }, 0);
        $this->identityKey = $identityKey;
    }

    /**
     * @param array $data
     * @return mixed
     * @throws SerializerException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function deserialize($data)
    {
        return $this->deserializeRecursive($data);
    }

    /**
     * @param $data
     * @return array|object
     * @throws SerializerException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    protected function deserializeRecursive($data)
    {
        if (is_array($data)) {
            if (!isset($data[$this->identityKey])) {
                $array = [];
                foreach ($data as $key => $value) {
                    $array[$key] = $this->deserializeRecursive($value);
                }
                return $array;
            }

            if (isset($this->identityMap[$data[$this->identityKey]])) {
                $class = $this->identityMap[$data[$this->identityKey]];

                if ($class == Id::class) {
                    return new Id($data['data']);
                }

                $reflection = $this->getReflectionClass($class, SerializerException::class);
                $object = $reflection->newInstanceWithoutConstructor();

                $reflectionProperties = $reflection->getProperties($this->reflectionProperties);
                foreach ($reflectionProperties as $reflectionProperty) {
                    if (isset($data['data'][$reflectionProperty->getName()])) {
                        $reflectionProperty->setAccessible(true);
                        $value = $data['data'][$reflectionProperty->getName()];
                        $reflectionProperty->setValue($object, $this->deserializeRecursive($value));
                    }
                }
                return $object;

            } else {
                throw new SerializerException("Trying to deserialize corrupted data. Invalid identity key '{$data[$this->identityKey]}'");
            }
        }

        return $data;
    }

    /**
     * @param object $complex
     * @return array
     * @throws SerializerException
     * @throws ReflectionException
     */
    public function serialize($complex)
    {
        return $this->serializeRecursive($complex);
    }

    /**
     * @param $something
     * @return array|mixed
     * @throws SerializerException
     * @throws ReflectionException
     */
    protected function serializeRecursive($something)
    {
        if (is_array($something)) {
            $data = [];
            foreach ($something as $key => $value) {
                $data[$key] = $this->serializeRecursive($value);
            }

            return $data;

        } elseif (is_object($something)) {
            $class = get_class($something);
            $reflection = $this->getReflectionClass($class, SerializerException::class);

            $data = [
                $this->identityKey => $this->classMap[$class],
                'data' => [],
            ];

            if ($something instanceof Id) {
                $data['data'] = $something->get();
                return $data;
            }

            $reflectionProperties = $reflection->getProperties($this->reflectionProperties);
            foreach ($reflectionProperties as $reflectionProperty) {
                $reflectionProperty->setAccessible(true);
                $value = $reflectionProperty->getValue($something);
                $data['data'][$reflectionProperty->getName()] = $this->serializeRecursive($value);
            }

            return $data;

        } elseif (is_scalar($something) || is_null($something)) {
            return $something;
        }

        $type = gettype($something);
        throw new SerializerException("Non-extractable type {$type}");
    }

    /**
     * @param $class
     * @param string $exceptionClass
     * @return ReflectionClass
     * @throws ReflectionException
     */
    protected function getReflectionClass($class, string $exceptionClass): ReflectionClass
    {
        if (!isset($this->classMap[$class])) {
            throw new $exceptionClass("Unknown class {$class} in " . __CLASS__);
        }

        if (!isset($this->reflectionClasses[$class])) {
            $this->reflectionClasses[$class] = new ReflectionClass($class);
        }
        return $this->reflectionClasses[$class];
    }

}