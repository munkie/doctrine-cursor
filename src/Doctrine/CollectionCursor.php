<?php

declare(strict_types=1);

namespace Mnk\Doctrine;

use Doctrine\Common\Collections\Collection;
use Mnk\Cursor\AbstractCursor;

/**
 * Doctrine Collection cursor
 * Better use with EXTRA_LAZY collections, cause slice() method load only items by offset and limit
 * While not extra lazy collections holds all values
 */
class CollectionCursor extends AbstractCursor
{

    /**
     * Collection of items
     *
     * @var Collection
     */
    private $collection;

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
    protected function doIterate(): \Traversable
    {
        yield from array_values(
            $this->collection->slice(
                $this->offset,
                $this->limit
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount(): int
    {
        return \count($this->collection);
    }
}
