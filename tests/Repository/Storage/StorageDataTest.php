<?php

namespace DiBify\DiBify\Repository\Storage;

use DiBify\DiBify\Manager\ModelManager;
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
        ModelManager::setScope(null);
        $this->dataNoScope = new StorageData($this->id, $this->body);
        $this->dataWithScope = new StorageData($this->id, $this->body, $this->scope);
    }

    public function testConstruct(): void
    {
        $data = new StorageData($this->id, $this->body);
        $this->assertNull($data->scope);
        ModelManager::setScope('test');
        $data = new StorageData($this->id, $this->body);
        $this->assertSame('test', $data->scope);
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
