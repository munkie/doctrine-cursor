<?php

declare(strict_types=1);

namespace Mnk\Cursor;

/**
 * Callback cursor
 */
class CallbackCursor implements CursorInterface
{

    /**
     * Query limit.
     *
     * @var int|null
     */
    private $limit;

    /**
     * Query offset.
     *
     * @var int
     */
    private $offset = 0;

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
     * Result.
     *
     * @var \Traversable
     */
    protected $result;

    /**
     * Count.
     *
     * @var int
     */
    protected $count;

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
        if (null === $this->result) {
            $this->result = ($this->resultCallback)($this->limit, $this->offset);
        }

        yield from $this->result;
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
        return iterator_to_array($this);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->count) {
            $this->count = ($this->countCallback)();
        }

        return $this->count;
    }

}
