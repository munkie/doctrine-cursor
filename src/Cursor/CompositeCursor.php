<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Double cursor
 * To combine two cursor and fetch data from them like working with only one cursor
 */
class CompositeCursor implements CursorInterface
{

    /**
     * First cursor
     * @var CursorInterface
     */
    private $firstCursor;

    /**
     * Second cursor
     * @var CursorInterface
     */
    private $secondCursor;

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
     * CompositeCursor constructor.
     * @param CursorInterface $firstCursor First cursor
     * @param CursorInterface $secondCursor Second cursor
     */
    public function __construct(CursorInterface $firstCursor, CursorInterface $secondCursor)
    {
        $this->firstCursor = $firstCursor;
        $this->secondCursor = $secondCursor;
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
        $firstCursorCount = $this->firstCursor->count();

        if ($this->offset >= $firstCursorCount) {
            $this->secondCursor->setOffset($this->offset - $firstCursorCount);
            $this->secondCursor->setLimit($this->limit);

            return $this->secondCursor->toArray();
        }

        $this->firstCursor->setOffset($this->offset);

        if (null === $this->limit) {
            return array_merge($this->firstCursor->toArray(), $this->secondCursor->toArray());
        }

        if ($this->offset + $this->limit <= $firstCursorCount) {
            $this->firstCursor->setLimit($this->limit);

            return $this->firstCursor->toArray();
        }

        $this->secondCursor->setLimit($this->offset + $this->limit - $firstCursorCount);

        return array_merge($this->firstCursor->toArray(), $this->secondCursor->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->firstCursor->count() + $this->secondCursor->count();
    }

}
