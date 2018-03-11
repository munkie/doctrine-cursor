<?php

namespace Mnk\Knp;

use Knp\Component\Pager\Event\ItemsEvent;
use Mnk\Cursor\CursorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for knp_pager.items event
 */
class KnpPagerCursorSubscriber implements EventSubscriberInterface
{

    /**
     * Handle knp pager items event
     *
     * @param ItemsEvent $event Knp Pager items event
     */
    public function items(ItemsEvent $event): void
    {
        if (!$event->target instanceof CursorInterface) {
            return;
        }

        $cursor = $event->target;

        $offset = $event->getOffset();
        $cursor->setLimit($event->getLimit());
        $cursor->setOffset($offset);

        $event->items = $cursor->getIterator();
        $event->count = $cursor->count();

        $event->stopPropagation();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 0]
        ];
    }
}
