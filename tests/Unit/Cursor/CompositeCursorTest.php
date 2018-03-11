<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Cursor;

use Mnk\Cursor\ArrayCursor;
use Mnk\Cursor\CompositeCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test for CompositeCursor @see CompositeCursor
 */
class CompositeCursorTest extends TestCase
{
    /**
     * Cursor under test
     *
     * @var CompositeCursor
     */
    private $cursor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cursor = new CompositeCursor(
            new ArrayCursor([1, 2, 3, 4, 5]),
            new ArrayCursor([6, 7, 8, 9])
        );
    }

    /**
     * Test count method return total count of two cursors
     */
    public function testCount()
    {
        static::assertCount(9, $this->cursor, 'Should return counts sum of two cursors');
    }

    /**
     * Data provider for testGetItems
     *
     * @return array
     */
    public static function itemsDataProvider(): array
    {
        return [
            '0 offset, no limit' => [
                'offset' => 0,
                'limit' => null,
                'expectedItems' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
            ],
            'offset inside 1st cursor' => [
                'offset' => 3,
                'limit' => null,
                'expectedItems' => [4, 5, 6, 7, 8, 9],
            ],
            'offset inside 1st cursor, with limit inside 1st cursor' => [
                'offset' => 3,
                'limit' => 1,
                'expectedItems' => [4],
            ],
            'offset inside 1st cursor, limit exceed' => [
                'offset' => 4,
                'limit' => 10,
                'expectedItems' => [5, 6, 7, 8, 9],
            ],
            'offset = num of items of 1st cursor' => [
                'offset' => 5,
                'limit' => null,
                'expectedItems' => [6, 7, 8, 9],
            ],
            'offset = num of items of 1st cursor, with limit' => [
                'offset' => 5,
                'limit' => 2,
                'expectedItems' => [6, 7],
            ],
            'offset exceed 1st cursor' => [
                'offset' => 6,
                'limit' => null,
                'expectedItems' => [7, 8, 9],
            ],
            'offset exceed 1st cursor, with limit' => [
                'offset' => 6,
                'limit' => 2,
                'expectedItems' => [7, 8],
            ],
            'limit outside of cursor' => [
                'offset' => 6,
                'limit' => 6,
                'expectedItems' => [7, 8, 9],
            ],
        ];
    }

    /**
     * Test getItems
     *
     * @dataProvider itemsDataProvider
     *
     * @param int $offset Offset
     * @param int|null $limit Limit
     * @param array $expectedItems Items
     */
    public function testToArray(int $offset, ?int $limit, array $expectedItems)
    {
        $this->cursor->setOffset($offset);
        $this->cursor->setLimit($limit);

        $result = $this->cursor->toArray();

        static::assertSame($expectedItems, $result, 'Incorrect items returned by method');
    }

    /**
     * Test getIterator
     *
     * @dataProvider itemsDataProvider
     *
     * @param int $offset Offset
     * @param int|null $limit Limit
     * @param array $expectedItems Items
     */
    public function testIterator(int $offset, ?int $limit, array $expectedItems)
    {
        $this->cursor->setOffset($offset);
        $this->cursor->setLimit($limit);

        static::assertSame($expectedItems, iterator_to_array($this->cursor), 'Iterator has incorrect items');
    }

    /**
     * Test when one of inner cursors is empty
     */
    public function testEmptyInnerCursor()
    {
        $cursor = new CompositeCursor(
            new ArrayCursor([]),
            new ArrayCursor([1, 2, 3]),
            new ArrayCursor([])
        );

        $cursor->setLimit(1);
        $cursor->setOffset(1);

        static::assertSame([2], $cursor->toArray());
        static::assertCount(3, $cursor);
    }
}
