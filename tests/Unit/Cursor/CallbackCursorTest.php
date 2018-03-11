<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Cursor;

use Mnk\Cursor\CallbackCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see CallbackCursor
 */
class CallbackCursorTest extends TestCase
{
    /**
     * Cursor to test
     *
     * @var CallbackCursor
     */
    private $cursor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cursor = new CallbackCursor(
            // returns ordered int array from 1 to 9 with applied offset limit
            function (?int $limit, int $offset) {
                if ($offset >= 9) {
                    return [];
                }
                $limit = $limit ?? 9;
                $max = min(9, $limit + $offset);
                return range($offset + 1, $max);
            },
            // max count of items in 1-9 int row is 9
            function () {
                return 9;
            }
        );
    }

    /**
     * Test count method return paginator count method result
     */
    public function testCount()
    {
        static::assertCount(9, $this->cursor, 'Incorrect count result returned by method');
    }

    /**
     * Test toArray
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
     * Test iterator
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

        static::assertSame($expectedItems, iterator_to_array($this->cursor), 'Incorrect iterator items');
    }

    /**
     * Data provider for testToArray
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
            'with offset, no limit' => [
                'offset' => 6,
                'limit' => null,
                'expectedItems' => [7, 8, 9],
            ],
            'with offset, with limit' => [
                'offset' => 2,
                'limit' => 3,
                'expectedItems' => [3, 4, 5],
            ],
            '0 offset, with limit' => [
                'offset' => 0,
                'limit' => 2,
                'expectedItems' => [1, 2],
            ],
            'with offset, with exceed limit' => [
                'offset' => 7,
                'limit' => 10,
                'expectedItems' => [8, 9],
            ],
            'out of bounds' => [
                'offset' => 10,
                'limit' => 10,
                'expectedItems' => [],
            ]
        ];
    }
}
