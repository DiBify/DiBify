<?php
/**
 * Created for dibify
 * Date: 28.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Exceptions\SerializerException;
use DiBify\DiBify\Model\Link;

class IdToLinkMapper implements MapperInterface
{

    /** @var string */
    private $alias;

    /** @var IdMapper */
    private $idMapper;

    /** @var bool */
    private $lazy;

    public function __construct(string $alias, bool $lazy = true)
    {
        $this->alias = $alias;
        $this->idMapper = new IdMapper();
        $this->lazy = $lazy;
    }

    /**
     * @param Link $complex
     * @return mixed|string
     * @throws SerializerException
     */
    public function serialize($complex)
    {
        if ($complex->getModelAlias() !== $this->alias) {
            throw new SerializerException("Link alias should be '{$this->alias}', but '{$complex->getModelAlias()}' received");
        }

        return $this->idMapper->serialize($complex->id());
    }

    /**
     * @inheritDoc
     */
    public function deserialize($data)
    {
        $id = $this->idMapper->deserialize($data);
        $link = Link::create($this->alias, $id);

        if ($this->lazy) {
            Link::preload($link);
        }

        return $link;
    }
}