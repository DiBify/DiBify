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

    /**
     * @var string
     */
    private $alias;

    private $idMapper;

    public function __construct(string $alias)
    {
        $this->alias = $alias;
        $this->idMapper = new IdMapper();
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
        return Link::create($this->alias, $id);
    }
}