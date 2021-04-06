<?php
/**
 * Created for dibify
 * Date: 25.01.2021
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace DiBify\DiBify\Repository\Components;


use DiBify\DiBify\Model\ModelInterface;
use Iterator;

class LazyIterator implements Iterator
{

    /** @var callable */
    protected $batchFetcher;

    protected int $batchSize;

    /** @var callable */
    protected $onBeforeBatch;

    /** @var callable */
    protected $onAfterBatch;

    private bool $preventPaginationOverlay;

    private Pagination $pagination;

    private array $ids = [];

    /** @var ModelInterface[] */
    private array $currentModels = [];

    private int $currentKey = 0;

    public function __construct(
        callable $batchFetcher,
        int $batchSize,
        bool $preventPaginationOverlay = true
    )
    {
        $this->batchFetcher = $batchFetcher;
        $this->batchSize = $batchSize;

        $this->pagination = new Pagination(1, $batchSize);
        $this->preventPaginationOverlay = $preventPaginationOverlay;

        $this->onBeforeBatch = function () {};
        $this->onAfterBatch = function () {};
    }

    public function setOnBeforeBatch(callable $onBeforeBatch): void
    {
        $this->onBeforeBatch = $onBeforeBatch;
    }

    public function setOnAfterBatch(callable $onAfterBatch): void
    {
        $this->onAfterBatch = $onAfterBatch;
    }

    public function current()
    {
        return $this->currentModels[$this->currentKey];
    }

    public function next(): void
    {
        $this->currentKey++;
        if ($this->currentKey == count($this->currentModels)) {
            $this->pagination->setNumber($this->pagination->getNumber() + 1);
            ($this->onAfterBatch)($this->currentModels);
            ($this->onBeforeBatch)();
            $this->fetchNext();
        }
    }

    public function key()
    {
        $model = $this->currentModels[$this->currentKey];
        return (string) $model->id();
    }

    public function valid(): bool
    {
        return isset($this->currentModels[$this->currentKey]);
    }

    public function rewind(): void
    {
        $this->pagination->setNumber(1);
        $this->ids = [];
        ($this->onBeforeBatch)();
        $this->fetchNext();
    }

    private function fetchNext(): void
    {
        $this->currentKey = 0;
        $this->currentModels = ($this->batchFetcher)($this->pagination);

        if ($this->preventPaginationOverlay) {
            $this->currentModels = array_values(array_filter($this->currentModels, function (ModelInterface $model) {
                $id = (string) $model->id();
                if ($isNew = !isset($this->ids[$id])) {
                    $this->ids[$id] = true;
                }
                return $isNew;
            }));
        }
    }
}