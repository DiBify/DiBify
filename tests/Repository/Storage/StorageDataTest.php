<?php

namespace DiBify\DiBify\Repository\Storage;

use PHPUnit\Framework\TestCase;

class StorageDataTest extends TestCase
{

    private string $id = '100';
    private array $body = ['hello' => 'world'];
    private string $scope = 'group';

    private StorageData $dataNoScope;
    private StorageData $dataWithScope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataNoScope = new StorageData($this->id, $this->body);
        $this->dataWithScope = new StorageData($this->id, $this->body, $this->scope);
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame(
            '{"scope":null,"id":"100","body":{"hello":"world"}}',
            json_encode($this->dataNoScope)
        );

        $this->assertSame(
            '{"scope":"group","id":"100","body":{"hello":"world"}}',
            json_encode($this->dataWithScope)
        );
    }

    public function testFromArray(): void
    {
        $this->assertEquals(
            StorageData::fromArray(['id' => $this->id, 'body' => $this->body]),
            $this->dataNoScope
        );

        $this->assertEquals(
            StorageData::fromArray(['id' => $this->id, 'body' => $this->body, 'scope' => $this->scope]),
            $this->dataWithScope
        );
    }

    public function testFromJson(): void
    {
        $this->assertEquals(
            StorageData::fromJson('{"scope":null,"id":"100","body":{"hello":"world"}}'),
            $this->dataNoScope
        );

        $this->assertEquals(
            StorageData::fromJson('{"scope":"group","id":"100","body":{"hello":"world"}}'),
            $this->dataWithScope
        );
    }
}
