<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Cursor;

use Mnk\Cursor\EmptyCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test EmptyCursor
 */
class EmptyCursorTest extends TestCase
{
    /**
     * Cursor to test
     *
     * @var EmptyCursor
     */
    private $cursor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cursor = new EmptyCursor();
    }

    /**
     * Test toArray returns empty array
     */
    public function testToArray()
    {
        $this->cursor->setLimit(10);
        $this->cursor->setOffset(5);

        static::assertSame([], $this->cursor->toArray(), 'Should always return empty array');
    }

    /**
     * Test count always returns 0
     */
    public function testCount()
    {
        static::assertCount(0, $this->cursor, 'Should return always 0 for count');
    }

    /**
     * Test getIterator returns empty Traversable iterator
     */
    public function testGetIterator()
    {
        $this->cursor->setLimit(10);
        $this->cursor->setOffset(5);

        static::assertSame([], iterator_to_array($this->cursor), 'Iterator should have no elements');
    }
}
