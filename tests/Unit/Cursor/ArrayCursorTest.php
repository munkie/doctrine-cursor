<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Cursor;

use Mnk\Cursor\ArrayCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see ArrayCursor
 */
class ArrayCursorTest extends TestCase
{
    /**
     * Cursor under test
     * @var ArrayCursor
     */
    private $cursor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cursor = new ArrayCursor([1, 2, 3, 4, 5, 6, 7, 8, 9]);
    }

    /**
     * Test getCount method return paginator count method result
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

        static::assertEquals($expectedItems, $result, 'Incorrect items returned by method');
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
        ];
    }

    /**
     * Test getIterator
     *
     * @dataProvider itemsDataProvider
     *
     * @param int $offset Offset
     * @param int|null $limit Limit
     * @param int[] $expectedItems Items
     */
    public function testGetIterator(int $offset, ?int $limit, array $expectedItems)
    {
        $this->cursor->setOffset($offset);
        $this->cursor->setLimit($limit);

        static::assertSame($expectedItems, iterator_to_array($this->cursor), 'Incorrect iterator items');
    }
}
