<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Array cursor
 */
class ArrayCursor implements CursorInterface
{

    /**
     * Items
     * @var array
     */
    private $items;

    /**
     * Limit
     * @var int|null
     */
    private $limit;

    /**
     * Offset
     * @var int
     */
    private $offset = 0;

    /**
     * Cursor constructor.
     * @param array $items Items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
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
        return \array_slice($this->items, $this->offset, $this->limit);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->items);
    }

}
