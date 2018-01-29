<?php

declare(strict_types=1);

namespace Mnk\Doctrine;

use Doctrine\Common\Collections\Collection;
use Mnk\Cursor\CursorInterface;

/**
 * Doctrine Collection cursor
 * Better use with EXTRA_LAZY collections, cause slice() method load only items by offset and limit
 * While not extra lazy collections holds all values
 */
class CollectionCursor implements CursorInterface
{

    /**
     * Collection of items
     *
     * @var Collection
     */
    private $collection;

    /**
     * Offset
     *
     * @var int
     */
    private $offset = 0;

    /**
     * Limit
     *
     * @var int|null
     */
    private $limit;

    /**
     * CollectionCursor constructor.
     *
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return array_values(
            $this->collection->slice(
                $this->offset,
                $this->limit
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->collection->count();
    }

}
