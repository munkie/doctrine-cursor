<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Composite cursor to iterate consecutively through multiple cursor
 */
class CompositeCursor extends AbstractCursor
{

    /**
     * Cursors to iterate throught
     *
     * @var CursorInterface[]
     */
    private $cursors;

    /**
     * @param CursorInterface[] $cursors Cursor to composite
     */
    public function __construct(CursorInterface ...$cursors)
    {
        $this->cursors = $cursors;
    }

    /**
     * {@inheritdoc}
     */
    protected function doIterate(): \Traversable
    {
        $outerOffset = 0;
        $limitLeft = $this->limit;

        foreach ($this->cursors as $cursor) {
            $count = \count($cursor);
            $outerOffset += $count;

            if ($this->offset >= $outerOffset) {
                continue;
            }
            $offset = ($this->offset > $outerOffset - $count) ? $this->offset - $outerOffset + $count : 0;
            $cursor->setOffset($offset);

            if (null !== $limitLeft) {
                $limit = min($count - $offset, $limitLeft);
                $cursor->setLimit($limit);
                $limitLeft -= $limit;
            }

            // "yield from" could be used here, but second "yield from" result keys will overwrite first yield keys
            // when iterator_to_array function is used with $use_keys flag=true
            foreach ($cursor as $item) {
                yield $item;
            }

            if (0 === $limitLeft) {
                break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount(): int
    {
        return (int) array_reduce(
            $this->cursors,
            function (int $carry, CursorInterface $cursor): int {
                return $carry + \count($cursor);
            },
            0
        );
    }
}
