<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Uses decorated cursor to get items, but always returns given count
 */
class FixedCountCursor implements CursorInterface
{
    /**
     * @var CursorInterface
     */
    private $innerCursor;

    /**
     * @var int
     */
    private $count;

    /**
     * @param CursorInterface $innerCursor Cursor to get items
     * @param int $count Fixed total count value
     */
    public function __construct(CursorInterface $innerCursor, int $count)
    {
        $this->innerCursor = $innerCursor;
        $this->count = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->innerCursor->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit(?int $limit): void
    {
        $this->innerCursor->setLimit($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset): void
    {
        $this->innerCursor->setOffset($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->innerCursor->toArray();
    }
}