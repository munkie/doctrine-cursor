<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Cursor;

use Mnk\Cursor\ArrayCursor;
use Mnk\Cursor\FixedCountCursor;
use PHPUnit\Framework\TestCase;

class FixedCountCursorTest extends TestCase
{
    public function testCount()
    {
        $innerCursor = new ArrayCursor([1, 2, 3]);
        $cursor = new FixedCountCursor($innerCursor, 10);

        static::assertCount(10, $cursor, 'Should return fixed value not real value from inner cursor');
    }

    public function testIterate()
    {
        $innerCursor = new ArrayCursor([1, 2, 3, 4, 5]);
        $cursor = new FixedCountCursor($innerCursor, 10);

        $cursor->setOffset(1);
        $cursor->setLimit(2);

        static::assertSame([2, 3], \iterator_to_array($cursor, false), 'Iterator returned invalid result');
    }

    public function testToArray()
    {
        $innerCursor = new ArrayCursor([1, 2, 3, 4, 5]);
        $cursor = new FixedCountCursor($innerCursor, 10);

        $cursor->setOffset(1);
        $cursor->setLimit(2);

        static::assertSame([2, 3], $cursor->toArray(), 'Iterator returned invalid result');
    }
}