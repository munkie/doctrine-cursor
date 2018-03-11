<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Knp;

use Knp\Component\Pager\Event\ItemsEvent;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Paginator;
use Mnk\Cursor\ArrayCursor;
use Mnk\Cursor\EmptyCursor;
use Mnk\Knp\KnpPagerCursorSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class KnpPagerCursorSubscriberTest extends TestCase
{
    public function testItemsEventsTargetIsCursor()
    {
        $subscriber = new KnpPagerCursorSubscriber();

        $offset = 1;
        $limit = 2;

        $cursor = new ArrayCursor(['a', 'b', 'c', 'd', 'e']);

        $event = new ItemsEvent($offset, $limit);
        $event->target = $cursor;

        $subscriber->items($event);

        static::assertInstanceOf(\Traversable::class, $event->items, 'Event items should be traversable');
        static::assertSame(
            ['b', 'c'],
            \iterator_to_array($event->items),
            'Event was not populated with items returned by Cursor'
        );
        static::assertSame(5, $event->count, 'Event count was not populated with cursor count');
    }

    /**
     * @dataProvider propagationProvider
     *
     * @param mixed $target Event target
     * @param bool $expected Expected value of propagation stopped flag
     */
    public function testItemsEventPropagationStopped($target, bool $expected)
    {
        $subscriber = new KnpPagerCursorSubscriber();

        $event = new ItemsEvent(10, 5);
        $event->target = $target;

        $subscriber->items($event);

        static::assertSame($expected, $event->isPropagationStopped(), 'Event propagation flag value is incorrect');
    }

    public static function propagationProvider(): array
    {
        return [
            'cursor should be stopped' => [
                'target' => new EmptyCursor(),
                'expected' => true,
            ],
            'stdClass should not be stopped' => [
                'target' => new \stdClass(),
                'expected' => false,
            ]
        ];
    }

    public function testPaginator()
    {
        $paginator = new Paginator();
        $paginator->subscribe(new KnpPagerCursorSubscriber());

        $target = new ArrayCursor([1, 2, 3, 4, 5]);
        /** @var AbstractPagination $pagination */
        $pagination = $paginator->paginate($target, 2, 3);

        $items = $pagination->getItems();
        static::assertInstanceOf(\Traversable::class, $items, 'Items should be traversable');
        static::assertSame([4, 5], \iterator_to_array($items, false), 'incorrect items returned by paginator');
        static::assertSame(5, $pagination->getTotalItemCount(), 'incorrect items returned by paginator');
        static::assertSame(2, $pagination->count(), 'incorrect items returned by paginator');
    }
}
