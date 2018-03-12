<?php

declare(strict_types=1);

namespace Mnk\Cursor;

class CallbackCursor extends AbstractCursor
{
    /**
     * Items callback,
     * should have following signature
     *
     * function (?int $limit, int $offset): iterable { ... }
     *
     * @var callable
     */
    private $itemsCallback;

    /**
     * Count callback
     * should have following signature
     *
     * function (): int { ... }
     *
     * @var callable
     */
    private $countCallback;

    /**
     * Constructor.
     *
     * @param callable $itemsCallback Items callback
     * @param callable $countCallback Count callback
     */
    public function __construct(callable $itemsCallback, callable $countCallback)
    {
        $this->itemsCallback = $itemsCallback;
        $this->countCallback = $countCallback;
    }

    /**
     * {@inheritdoc}
     */
    protected function doIterate(): \Traversable
    {
        yield from ($this->itemsCallback)($this->limit, $this->offset);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount(): int
    {
        return ($this->countCallback)();
    }

}
