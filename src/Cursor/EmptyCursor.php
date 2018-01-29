<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Empty cursor, has not items
 */
class EmptyCursor implements CursorInterface
{

    /**
     * {@inheritdoc}
     */
    public function setLimit(?int $limit): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator();
    }

}
