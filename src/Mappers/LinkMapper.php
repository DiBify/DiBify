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

    public function __construct()
    {
        parent::__construct(Link::class, [
            'id' => new IdMapper(),
            'model' => new StringMapper()
        ]);
    }

}