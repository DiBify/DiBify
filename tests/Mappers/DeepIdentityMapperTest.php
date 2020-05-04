<?php
/**
 * Created for dibify
 * Date: 04.05.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Mock\TestModel_1;
use DiBify\DiBify\Mock\TestModel_2;
use DiBify\DiBify\Mock\TestModel_3;

class DeepIdentityMapperTest extends MapperTestCase
{
    public function serializeDataProvider(): array
    {
        $model_1 = new TestModel_1(1, 2, 3);
        $model_2 = new TestModel_2(4, 5, $model_1);
        return [
            [
                $model_1,
                [
                    '__type' => 'test_1',
                    'data' => [
                        'id' => [
                            '__type' => 'id',
                            'data' => '1',
                        ],
                        'otherId' => [
                            '__type' => 'id',
                            'data' => '2',
                        ],
                        'custom' => 3,
                        'onBeforeCommit' => false,
                        'onAfterCommit' => false,
                    ],
                ],
            ],
            [
                $model_2,
                [
                    '__type' => 'test_2',
                    'data' => [
                        'id' => [
                            '__type' => 'id',
                            'data' => '4',
                        ],
                        'otherId' => [
                            '__type' => 'id',
                            'data' => '5',
                        ],
                        'custom' => [
                            '__type' => 'test_1',
                            'data' => [
                                'id' => [
                                    '__type' => 'id',
                                    'data' => '1',
                                ],
                                'otherId' => [
                                    '__type' => 'id',
                                    'data' => '2',
                                ],
                                'custom' => 3,
                                'onBeforeCommit' => false,
                                'onAfterCommit' => false,
                            ],
                        ],
                        'onBeforeCommit' => false,
                        'onAfterCommit' => false,
                    ],
                ],
            ],
            [
                [$model_1, $model_2, 'string', 10, true, null, ['key' => 'value']],
                [
                    [
                        '__type' => 'test_1',
                        'data' => [
                            'id' => [
                                '__type' => 'id',
                                'data' => '1',
                            ],
                            'otherId' => [
                                '__type' => 'id',
                                'data' => '2',
                            ],
                            'custom' => 3,
                            'onBeforeCommit' => false,
                            'onAfterCommit' => false,
                        ],
                    ],
                    [
                        '__type' => 'test_2',
                        'data' => [
                            'id' => [
                                '__type' => 'id',
                                'data' => '4',
                            ],
                            'otherId' => [
                                '__type' => 'id',
                                'data' => '5',
                            ],
                            'custom' => [
                                '__type' => 'test_1',
                                'data' => [
                                    'id' => [
                                        '__type' => 'id',
                                        'data' => '1',
                                    ],
                                    'otherId' => [
                                        '__type' => 'id',
                                        'data' => '2',
                                    ],
                                    'custom' => 3,
                                    'onBeforeCommit' => false,
                                    'onAfterCommit' => false,
                                ],
                            ],
                            'onBeforeCommit' => false,
                            'onAfterCommit' => false,
                        ],
                    ],
                    'string',
                    10,
                    true,
                    null,
                    ['key' => 'value']
                ],
            ],
        ];
    }

    public function serializeInvalidDataProvider(): array
    {
        return [
            [tmpfile()],
            [$this],
            [new TestModel_3()],
        ];
    }

    public function deserializeDataProvider(): array
    {
        $model_1 = new TestModel_1(1, 2, 3);
        $model_2 = new TestModel_2(4, 5, $model_1);
        return [
            [
                [
                    '__type' => 'test_1',
                    'data' => [
                        'id' => [
                            '__type' => 'id',
                            'data' => '1',
                        ],
                        'otherId' => [
                            '__type' => 'id',
                            'data' => '2',
                        ],
                        'custom' => 3,
                        'onBeforeCommit' => false,
                        'onAfterCommit' => false,
                    ],
                ],
                $model_1,
            ],
            [
                [
                    '__type' => 'test_2',
                    'data' => [
                        'id' => [
                            '__type' => 'id',
                            'data' => '4',
                        ],
                        'otherId' => [
                            '__type' => 'id',
                            'data' => '5',
                        ],
                        'custom' => [
                            '__type' => 'test_1',
                            'data' => [
                                'id' => [
                                    '__type' => 'id',
                                    'data' => '1',
                                ],
                                'otherId' => [
                                    '__type' => 'id',
                                    'data' => '2',
                                ],
                                'custom' => 3,
                                'onBeforeCommit' => false,
                                'onAfterCommit' => false,
                            ],
                        ],
                        'onBeforeCommit' => false,
                        'onAfterCommit' => false,
                    ],
                ],
                $model_2,
            ],
            [
                [
                    [
                        '__type' => 'test_1',
                        'data' => [
                            'id' => [
                                '__type' => 'id',
                                'data' => '1',
                            ],
                            'otherId' => [
                                '__type' => 'id',
                                'data' => '2',
                            ],
                            'custom' => 3,
                            'onBeforeCommit' => false,
                            'onAfterCommit' => false,
                        ],
                    ],
                    [
                        '__type' => 'test_2',
                        'data' => [
                            'id' => [
                                '__type' => 'id',
                                'data' => '4',
                            ],
                            'otherId' => [
                                '__type' => 'id',
                                'data' => '5',
                            ],
                            'custom' => [
                                '__type' => 'test_1',
                                'data' => [
                                    'id' => [
                                        '__type' => 'id',
                                        'data' => '1',
                                    ],
                                    'otherId' => [
                                        '__type' => 'id',
                                        'data' => '2',
                                    ],
                                    'custom' => 3,
                                    'onBeforeCommit' => false,
                                    'onAfterCommit' => false,
                                ],
                            ],
                            'onBeforeCommit' => false,
                            'onAfterCommit' => false,
                        ],
                    ],
                    'string',
                    10,
                    true,
                    null,
                    ['key' => 'value']
                ],
                [$model_1, $model_2, 'string', 10, true, null, ['key' => 'value']],
            ],
        ];
    }

    public function deserializeInvalidDataProvider(): array
    {
        return [
            [
                [
                    '__type' => 'test_3',
                    'data' => [
                        'id' => [
                            '__type' => 'id',
                            'data' => '1',
                        ],
                        'otherId' => [
                            '__type' => 'id',
                            'data' => '2',
                        ],
                        'custom' => 3,
                        'onBeforeCommit' => false,
                        'onAfterCommit' => false,
                    ],
                ],
            ]
        ];
    }

    protected function assert($expected, $actual)
    {
        $this->assertEquals(
            $expected,
            $actual
        );
    }

    protected function getMapper(): MapperInterface
    {
        return new DeepIdentityMapper(
            [
                TestModel_1::class => 'test_1',
                TestModel_2::class => 'test_2',
            ],
            '__type'
        );
    }
}
