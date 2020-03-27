<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:14
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Model\Link;

class LinkMapper extends ObjectMapper
{

    /** @var bool */
    private $lazy;

    public function __construct(bool $lazy = true)
    {
        $this->lazy = $lazy;
        parent::__construct(Link::class, [
            'id' => new IdMapper(),
            'alias' => new StringMapper()
        ]);
    }

    public function deserialize($data)
    {
        /** @var Link $link */
        $link = parent::deserialize($data);

        if (!$this->lazy) {
            Link::preload($link);
        }

        return $link;
    }

}