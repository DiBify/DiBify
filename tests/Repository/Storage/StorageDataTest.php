<?php

namespace DiBify\DiBify\Repository\Storage;

use PHPUnit\Framework\TestCase;

class StorageDataTest extends TestCase
{

    private string $id = '100';
    private array $body = ['hello' => 'world'];
    private StorageData $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->data = new StorageData($this->id, $this->body);
    }

    public function testJsonSerialize(): void
    {
        $this->assertSame(
            '{"id":"100","body":{"hello":"world"}}',
            json_encode($this->data)
        );
    }

    public function testFromArray(): void
    {
        $this->assertEquals(
            StorageData::fromArray(['id' => $this->id, 'body' => $this->body]),
            $this->data
        );
    }

    public function testFromJson(): void
    {
        $this->assertEquals(
            StorageData::fromJson('{"id":"100","body":{"hello":"world"}}'),
            $this->data
        );
    }
}
