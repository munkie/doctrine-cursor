<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Doctrine;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Mnk\Doctrine\DoctrineOrmNativeQueryCursor;
use Mnk\Tests\Functional\BaseDoctrineTestCase;
use Mnk\Tests\Functional\Fixtures\Entity\Message;

class DoctrineOrmNativeQueryCursorTest extends BaseDoctrineTestCase
{
    public function testItemsQueryWithPartialHydration()
    {
        $this->sqlLogger->enabled = true;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Message::class, 'm');
        $rsm->addFieldResult('m', 'id', 'id');
        $rsm->addFieldResult('m', 'body', 'body');

        $itemsSql = 'SELECT id, body FROM message WHERE topic_id = :topic_id';

        $itemsQuery = new NativeQuery($this->entityManager);
        $itemsQuery->setSQL($itemsSql);
        $itemsQuery->setResultSetMapping($rsm);
        $itemsQuery->setParameter('topic_id', 1);

        $countQuery = new NativeQuery($this->entityManager);
        $countQuery->setSQL('SELECT COUNT(*) as cnt FROM message WHERE topic_id = :topic_id');
        $countQuery->setParameter('topic_id', 1);

        $cursor = new DoctrineOrmNativeQueryCursor($itemsQuery, $countQuery);
        $cursor->setOffset(1);
        $cursor->setLimit(2);

        /** @var Message[] $items */
        $items = $cursor->toArray();
        static::assertCount(2, $items);

        static::assertInstanceOf(Message::class, $items[0]);
        static::assertSame(11, $items[0]->getId());
        static::assertSame('Body #11', $items[0]->getBody());
        static::assertNull($items[0]->getCreatedAt(), 'CreatedAt should not be hydrated');

        static::assertInstanceOf(Message::class, $items[1]);
        static::assertSame(12, $items[1]->getId());
        static::assertSame('Body #12', $items[1]->getBody());
        static::assertNull($items[1]->getCreatedAt(), 'CreatedAt should not be hydrated');

        $expectedSqls = [
            'SELECT id, body FROM message WHERE topic_id = :topic_id LIMIT 2 OFFSET 1',
        ];
        $this->assertLoggedSqls($expectedSqls);
    }

    public function testCountQueryWithScalarResultSetMapping()
    {
        $itemsQuery = new NativeQuery($this->entityManager);

        $countQuery = new NativeQuery($this->entityManager);
        $countQuery->setSQL('SELECT COUNT(*) as cnt FROM message WHERE topic_id = :topic_id');
        $countQuery->setParameter('topic_id', 1);

        $cursor = new DoctrineOrmNativeQueryCursor($itemsQuery, $countQuery);

        static::assertCount(0, $cursor, 'Without properly set result set mapping count should return 0');
    }

    public function testCountQueryWithoutScalarResultSetMapping()
    {
        $itemsQuery = new NativeQuery($this->entityManager);

        $countQuery = new NativeQuery($this->entityManager);
        $countQuery->setSQL('SELECT COUNT(*) as cnt FROM message WHERE topic_id = :topic_id');
        $countQuery->setParameter('topic_id', 1);

        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('cnt', 'count', Type::INTEGER);
        $countQuery->setResultSetMapping($resultSetMapping);

        $cursor = new DoctrineOrmNativeQueryCursor($itemsQuery, $countQuery);

        static::assertCount(20, $cursor, 'With properly set result set mapping count should return 20');
    }
}