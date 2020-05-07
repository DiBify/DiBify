<?php
/**
 * Created for dibify
 * Date: 11.04.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Storage;


class StorageData
{

    /** @var string */
    public $id;

    /** @var array */
    public $body;

    public function __construct(string $id, array $body)
    {
        $this->id = $id;
        $this->body = $body;
    }

}