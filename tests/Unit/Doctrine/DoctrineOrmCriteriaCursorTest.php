<?php

declare(strict_types=1);

namespace Mnk\Tests\Unit\Doctrine;

use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Mnk\Doctrine\DoctrineOrmCriteriaCursor;
use PHPUnit\Framework\TestCase;

class DoctrineOrmCriteriaCursorTest extends TestCase
{
    public function testCount()
    {
        $persister = $this->createMock(EntityPersister::class);
        $criteria = ['name' => 'John'];
        $orderBy = ['id' => 'DESC'];

        $cursor = new DoctrineOrmCriteriaCursor($persister, $criteria, $orderBy);

        $persister->expects(static::once())
            ->method('count')
            ->willReturn(10);

        static::assertCount(10, $cursor, 'Invalid count result');
    }

    public function testIterate()
    {
        $persister = $this->createMock(EntityPersister::class);

        $criteria = ['name' => 'John'];
        $orderBy = ['id' => 'DESC'];

        $limit = 5;
        $offset = 10;
        $items = [1, 2, 3];

        $cursor = new DoctrineOrmCriteriaCursor($persister, $criteria, $orderBy);

        $persister->expects(static::once())
            ->method('loadAll')
            ->with($criteria, $orderBy, $limit, $offset)
            ->willReturn($items);

        $cursor->setLimit($limit);
        $cursor->setOffset($offset);

        static::assertSame($items, \iterator_to_array($cursor, false), 'Invalid items returned');
    }

    public function testIterateDefaultValues()
    {
        $persister = $this->createMock(EntityPersister::class);

        $limit = 5;
        $offset = 10;
        $items = [1, 2, 3];

        $cursor = new DoctrineOrmCriteriaCursor($persister);

        $persister->expects(static::once())
            ->method('loadAll')
            ->with([], null, $limit, $offset)
            ->willReturn($items);

        $cursor->setLimit($limit);
        $cursor->setOffset($offset);

        static::assertSame($items, \iterator_to_array($cursor, false), 'Invalid items returned');
    }

    public function testToArray()
    {
        $persister = $this->createMock(EntityPersister::class);

        $criteria = ['name' => 'John'];
        $orderBy = ['id' => 'DESC'];

        $limit = 5;
        $offset = 10;
        $items = [1, 2, 3];

        $cursor = new DoctrineOrmCriteriaCursor($persister, $criteria, $orderBy);

        $persister->expects(static::once())
            ->method('loadAll')
            ->with($criteria, $orderBy, $limit, $offset)
            ->willReturn($items);

        $cursor->setLimit($limit);
        $cursor->setOffset($offset);

        static::assertSame($items, $cursor->toArray(), 'Invalid items returned');
    }
}
