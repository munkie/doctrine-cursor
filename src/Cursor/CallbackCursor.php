<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Callback cursor
 */
class CallbackCursor extends AbstractCursor
{

    /**
     * Result callback
     *
     * @var callable
     */
    private $resultCallback;

    /**
     * Count callback
     *
     * @var callable
     */
    private $countCallback;

    /**
     * Constructor.
     *
     * @param callable $resultCallback Result callback
     * @param callable $countCallback Count callback
     */
    public function __construct(callable $resultCallback, callable $countCallback)
    {
        $this->resultCallback = $resultCallback;
        $this->countCallback = $countCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from ($this->resultCallback)($this->limit, $this->offset);
    }

    /**
     * {@inheritdoc}
     */
    protected function doCount(): int
    {
        return ($this->countCallback)();
    }

}
