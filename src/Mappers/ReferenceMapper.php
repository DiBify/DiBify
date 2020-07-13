<?php
/**
 * Created for DiBify.
 * Datetime: 02.08.2018 15:14
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace DiBify\DiBify\Mappers;


use DiBify\DiBify\Model\Reference;

class ReferenceMapper extends ObjectMapper
{

    /** @var bool */
    private $eager;

    public function __construct(bool $eager = false)
    {
        $this->eager = $eager;
        parent::__construct(Reference::class, [
            'id' => new IdMapper(),
            'alias' => new StringMapper()
        ]);
    }

    public function deserialize($data)
    {
        $reference = Reference::create($data['alias'], $data['id']);

        if ($this->eager) {
            Reference::preload($reference);
        }

        return $reference;
    }

}