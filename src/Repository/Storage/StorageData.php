<?php
/**
 * Created for dibify
 * Date: 11.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Storage;


use JsonSerializable;

class StorageData implements JsonSerializable
{

    public string $id;

    public array $body;

    public function __construct(string $id, array $body)
    {
        $this->id = $id;
        $this->body = $body;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
        ];
    }

    public static function fromArray(array $array): StorageData
    {
        return new StorageData($array['id'], $array['body']);
    }

    public static function fromJson(string $json): StorageData
    {
        return self::fromArray(json_decode($json, true));
    }

}