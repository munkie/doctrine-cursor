<?php

declare(strict_types=1);

namespace Mnk\Cursor;

class ArrayCursor extends AbstractCursor
{
    /**
     * Items
     * @var array
     */
    private $items;

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
    protected function doIterate(): \Traversable
    {
        return new \ArrayIterator(
            \array_slice($this->items, $this->offset, $this->limit)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount(): int
    {
        return \count($this->items);
    }
}
