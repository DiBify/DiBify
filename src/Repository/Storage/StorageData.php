<?php
/**
 * Created for dibify
 * Date: 11.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Storage;


use DiBify\DiBify\Manager\ModelManager;
use JsonSerializable;

class StorageData implements JsonSerializable
{

    public ?string $scope = null;

    public string $id;

    public array $body;

    public function __construct(string $id, array $body, ?string $scope = null)
    {
        $this->scope = $scope ?? ModelManager::getScope();
        $this->id = $id;
        $this->body = $body;
    }

    public function jsonSerialize(): array
    {
        return [
            'scope' => $this->scope,
            'id' => $this->id,
            'body' => $this->body,
        ];
    }

    public static function fromArray(array $array): StorageData
    {
        $data = new StorageData($array['id'], $array['body']);
        $data->scope = $array['scope'] ?? null;
        return $data;
    }

    public static function fromJson(string $json): StorageData
    {
        return self::fromArray(json_decode($json, true));
    }

}