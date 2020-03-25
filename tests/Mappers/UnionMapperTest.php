<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 28.10.2019 2:15
 */

namespace DiBify\DiBify\Mappers;

use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Id\Id;
use DiBify\DiBify\Mappers\Components\UnionRule;
use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use Throwable;

class UnionMapperTest extends MapperTestCase
{

    public function serializeDataProvider(): array
    {
        return [
            ['string', 'string'],
            [1, 1],
            [
                new TestModel_1(1, 2, 3),
                ['id' => '1', 'otherId' => '2', 'custom' => 3, 'model' => 'TestModel'],
            ],
            [
                new TestModel_2(1, 2, 'three'),
                ['id' => '1', 'otherId' => '2', 'custom' => 'three', 'model' => 'TestSecondModel'],
            ],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [1.1],
            [[]],
            [null],
            [false],
            [new Id(1)],
        ];
    }

    public function deserializeDataProvider(): array
    {
        return [
            ['string', 'string'],
            [1, 1],
            [
                ['id' => '1', 'otherId' => '2', 'custom' => 3, 'model' => 'TestModel'],
                new TestModel_1(1, 2, 3),
            ],
            [
                ['id' => '1', 'otherId' => '2', 'custom' => 'three', 'model' => 'TestSecondModel'],
                new TestModel_2(1, 2, 'three'),
            ],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            [1.1],
            [[]],
            [null],
            [false],
            [new Id(1)],
            [['id' => '1', 'otherId' => '2', 'custom' => 3]]
        ];
    }

    /**
     * @param $input
     * @throws SerializerException
     * @dataProvider deserializeInvalidDataProvider
     */
    public function testInvalidDeserialize($input)
    {
        $this->expectException(Throwable::class);
        $this->getMapper()->deserialize($input);
    }

    protected function getMapper(): MapperInterface
    {
        $string = new UnionRule(
            new StringMapper(),
            function ($complex, $serialized) {
                return $serialized;
            },
            function ($serialized) {
                if (is_string($serialized)) {
                    return $serialized;
                }

                throw new SerializerException('Only string serialization');
            }
        );

        $int = new UnionRule(
            new IntMapper(),
            function ($complex, $serialized) {
                return $serialized;
            },
            function ($serialized) {
                if (is_int($serialized)) {
                    return $serialized;
                }

                throw new SerializerException('Only integer serialization');
            }
        );

        $testModel = new UnionRule(
            new ObjectMapper(
                TestModel_1::class,
                [
                    'id' => new IdMapper(),
                    'otherId' => new IdMapper(),
                    'custom' => new IntMapper(),
                ]
            ),
            function ($complex, $serialized) {
                $serialized['model'] = 'TestModel';
                return $serialized;
            },
            function ($serialized) {
                if (isset($serialized['model']) && $serialized['model'] === 'TestModel') {
                    return $serialized;
                }

                throw new SerializerException('Only TestModel serialization');
            }
        );

        $testSecondModel = new UnionRule(
            new ObjectMapper(
                TestModel_2::class,
                [
                    'id' => new IdMapper(),
                    'otherId' => new IdMapper(),
                    'custom' => new StringMapper(),
                ]
            ),
            function ($complex, $serialized) {
                $serialized['model'] = 'TestSecondModel';
                return $serialized;
            },
            function ($serialized) {
                if (isset($serialized['model']) && $serialized['model'] === 'TestSecondModel') {
                    return $serialized;
                }

                throw new SerializerException('Only TestSecondModel serialization');
            }
        );


        return new UnionMapper([$string, $int, $testModel, $testSecondModel]);
    }
}
