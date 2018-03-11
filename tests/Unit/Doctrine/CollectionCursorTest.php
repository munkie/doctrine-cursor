<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Mnk\Doctrine\CollectionCursor;
use PHPUnit\Framework\TestCase;

/**
 * Tests for @see CollectionCursor
 */
class CollectionCursorTest extends TestCase
{
    /**
     * Test get iterator
     *
     * @dataProvider itemsProvider
     *
     * @param int $offset Offset to apply
     * @param int|null $limit Limit to apply
     * @param array $expected Expected result
     */
    public function testGetIterator(int $offset, ?int $limit, array $expected)
    {
        $items = [1, 2, 3, 4, 5];
        $collection = new ArrayCollection($items);
        $cursor = new CollectionCursor($collection);

        $cursor->setOffset($offset);
        $cursor->setLimit($limit);

        static::assertSame($expected, iterator_to_array($cursor), 'Incorrect result');
    }

    /**
     * Test getItems
     *
     * @dataProvider itemsProvider
     *
     * @param int $offset Offset to apply
     * @param int|null $limit Limit to apply
     * @param array $expected Expected result
     */
    public function testToArray(int $offset, ?int $limit, array $expected)
    {
        $collection = new ArrayCollection([1, 2, 3, 4, 5]);
        $cursor = new CollectionCursor($collection);

        $cursor->setOffset($offset);
        $cursor->setLimit($limit);

        static::assertSame($expected, $cursor->toArray(), 'Incorrect result');
    }

    /**
     * Data provider with offset limit and expected result
     *
     * @return array
     */
    public static function itemsProvider(): array
    {
        return [
            '2,3' => [
                'offset' => 2,
                'limit' => 3,
                'expected' => [3, 4, 5],
            ],
            '0,null' => [
                'offset' => 0,
                'limit' => null,
                'expected' => [1, 2, 3, 4, 5],
            ],
            '3,null' => [
                'offset' => 3,
                'limit' => null,
                'expected' => [4, 5],
            ],
            '1,1' => [
                'offset' => 1,
                'limit' => 1,
                'expected' => [2],
            ],
        ];
    }

    /**
     * Test count
     *
     * @dataProvider countProvider
     *
     * @param array $items Items to populate collection
     * @param int $offset Offset to apply
     * @param int|null $limit Limit to apply
     * @param int $expectedCount Expected count
     *
     * @internal param array $expected Expected result
     */
    public function testGetCount(array $items, int $offset, ?int $limit, int $expectedCount)
    {
        $collection = new ArrayCollection($items);
        $cursor = new CollectionCursor($collection);

        $cursor->setOffset($offset);
        $cursor->setLimit($limit);

        static::assertCount($expectedCount, $cursor, 'Incorrect result');
    }

    /**
     * Data provider for @see testGetCount
     *
     * @return array
     */
    public static function countProvider(): array
    {
        return [
            'no offset, limit' => [
                'items' => [1, 2, 3],
                'offset' => 0,
                'limit' => null,
                'expected' => 3,
            ],
            'offset and limit' => [
                'items' => [1, 2, 3],
                'offset' => 1,
                'limit' => 1,
                'expected' => 3,
            ],
            'empty items' => [
                'items' => [],
                'offset' => 1,
                'limit' => 1,
                'expected' => 0,
            ],
        ];
    }
}
