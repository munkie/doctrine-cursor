<?php

declare(strict_types=1);

namespace Mnk\Tests\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mnk\Doctrine\DoctrineQueryCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test @see DoctrineQueryCursor
 */
class DoctrineQueryCursorTest extends TestCase
{
    /**
     * Entity manager mock
     *
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManagerMock;

    /**
     * ORM configuration mock
     *
     * @var Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurationMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->configurationMock = $this->createMock(Configuration::class);
    }

    /**
     * Test setLimit
     */
    public function testSetLimit()
    {
        $limit = 10;

        $itemsQuery = $this->createQuery();
        $countQuery = $this->createQuery();
        $cursor = new DoctrineQueryCursor($itemsQuery, $countQuery);

        $cursor->setLimit($limit);

        static::assertSame($limit, $itemsQuery->getMaxResults(), 'Incorrect limit set in items query');
        static::assertNull($countQuery->getMaxResults(), 'Limit should not be set in count query');
    }

    /**
     * Test setOffset
     */
    public function testSetOffset()
    {
        $offset = 10;

        $itemsQuery = $this->createQuery();
        $countQuery = $this->createQuery();
        $cursor = new DoctrineQueryCursor($itemsQuery, $countQuery);

        $cursor->setOffset($offset);

        static::assertSame($offset, $itemsQuery->getFirstResult(), 'Incorrect offset set in items query');
        static::assertNull($countQuery->getFirstResult(), 'Offset should not be set in count query');
    }

    /**
     * Test cursor as iterator
     */
    public function testIterator()
    {
        $result = [1, 2, 3];
        $itemsQueryMock = $this->createItemsQueryMock($result);

        $countQuery = $this->createQuery();
        $cursor = new DoctrineQueryCursor($itemsQueryMock, $countQuery);

        static::assertSame($result, iterator_to_array($cursor), 'Iterator values are incorrect');
    }

    /**
     * Test items query is not executed when limit is 0
     */
    public function testItemsQueryIsNotExecutedWhenLimitIsZero()
    {
        $itemsQueryMock = $this->createQueryMock('execute', 'setMaxResults', 'getMaxResults');
        $itemsQueryMock->expects(static::never())
            ->method('execute');
        $itemsQueryMock->expects(static::once())
            ->method('setMaxResults')
            ->with(0);
        $itemsQueryMock->method('getMaxResults')
            ->willReturn(0);

        $countQuery = $this->createQuery();
        $cursor = new DoctrineQueryCursor($itemsQueryMock, $countQuery);
        $cursor->setLimit(0);

        static::assertSame([], iterator_to_array($cursor), 'Iterator should return empty array');
    }

    /**
     * Test toArray method
     */
    public function testToArray()
    {
        $result = [1, 2, 3];
        $itemsQueryMock = $this->createItemsQueryMock($result);

        $countQuery = $this->createQuery();
        $cursor = new DoctrineQueryCursor($itemsQueryMock, $countQuery);

        static::assertSame($result, $cursor->toArray(), 'Returned items array is incorrect');
    }

    /**
     * Test count() method
     */
    public function testCount()
    {
        $count = 42;

        $itemsQuery = $this->createQuery();
        $countQuery = $this->createQueryMock('getSingleScalarResult');
        $countQuery->expects(static::once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $cursor = new DoctrineQueryCursor($itemsQuery, $countQuery);

        static::assertCount($count, $cursor, 'Incorrect count returned');
    }

    /**
     * Test count() method is called more then once will trigger only one db query
     */
    public function testCountQueryIsPerformedOnce()
    {
        $count = 42;

        $itemsQuery = $this->createQuery();
        $countQuery = $this->createQueryMock('getSingleScalarResult');
        $countQuery->expects(static::once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $cursor = new DoctrineQueryCursor($itemsQuery, $countQuery);

        static::assertCount($count, $cursor, 'Incorrect count returned for first time');
        static::assertCount($count, $cursor, 'Incorrect count returned for second time');
    }

    /**
     * Test createCountQueryBuilder method
     *
     * @dataProvider aliasProvider
     *
     * @param bool $distinct Distinct flag value
     * @param string|null $fromAlias from alias
     * @param string $expectedDQL Expected DQL to be generated
     */
    public function testCreateCountQueryBuilder(bool $distinct, ?string $fromAlias, string $expectedDQL)
    {
        $this->entityManagerMock->method('createQuery')
            ->willReturnCallback(
                function ($dql) {
                    return $this->createQuery($dql);
                }
            );

        $queryBuilder = new QueryBuilder($this->entityManagerMock);
        $queryBuilder
            ->from('AAA', 'a')
            ->leftJoin('BBB', 'b')
            ->select('a, b')
            ->orderBy('a.id', 'DESC');

        $countQuery = DoctrineQueryCursor::createCountQueryBuilder($queryBuilder, $distinct, $fromAlias);

        static::assertSame(
            $expectedDQL,
            $countQuery->getDQL(),
            'Incorrect count query generated'
        );
    }

    /**
     * Data provider for @see testCreateCountQueryBuilder
     * To test case when alias is autodetected from query builder
     *
     * @return array
     */
    public static function aliasProvider(): array
    {
        return [
            'not distinct, without alias' => [
                'distinct' => false,
                'alias' => null,
                'DQL' => 'SELECT COUNT(1) FROM AAA a LEFT JOIN BBB b',
            ],
            'not distinct, with alias a' => [
                'distinct' => false,
                'alias' => 'a',
                'DQL' => 'SELECT COUNT(1) FROM AAA a LEFT JOIN BBB b',
            ],
            'not distinct, with alias b' => [
                'distinct' => false,
                'alias' => 'b',
                'DQL' => 'SELECT COUNT(1) FROM AAA a LEFT JOIN BBB b',
            ],
            'distinct, without alias' => [
                'distinct' => true,
                'alias' => null,
                'DQL' => 'SELECT COUNT(DISTINCT a) FROM AAA a LEFT JOIN BBB b',
            ],
            'distinct, with alias a' => [
                'distinct' => true,
                'alias' => 'a',
                'DQL' => 'SELECT COUNT(DISTINCT a) FROM AAA a LEFT JOIN BBB b',
            ],
            'distinct, with alias b' => [
                'distinct' => true,
                'alias' => 'b',
                'DQL' => 'SELECT COUNT(DISTINCT b) FROM AAA a LEFT JOIN BBB b',
            ],
        ];
    }

    /**
     * Test fromQueryBuilder method
     */
    public function testFromQueryBuilder()
    {
        $this->entityManagerMock->method('createQuery')
            ->willReturnCallback(
                function ($dql) {
                    return $this->createQuery($dql);
                }
            );

        $queryBuilder = new QueryBuilder($this->entityManagerMock);
        $queryBuilder
            ->from('AAA', 'a')
            ->leftJoin('BBB', 'b')
            ->select('a, b')
            ->orderBy('a.id', 'DESC');

        $cursor = DoctrineQueryCursor::fromQueryBuilder($queryBuilder);

        /** @var Query $itemsQuery */
        $itemsQuery = $this->getObjectPrivatePropertyValue($cursor, 'itemsQuery');
        /** @var Query $countQuery */
        $countQuery = $this->getObjectPrivatePropertyValue($cursor, 'countQuery');

        static::assertSame(
            'SELECT a, b FROM AAA a LEFT JOIN BBB b ORDER BY a.id DESC',
            $itemsQuery->getDQL(),
            'Incorrect items query DQL'
        );
        static::assertSame(
            'SELECT COUNT(1) FROM AAA a LEFT JOIN BBB b',
            $countQuery->getDQL(),
            'Incorrect count query generated'
        );
    }

    /**
     * Create ORM query with mocked entity manager
     *
     * @param string|null $dql Query DQL
     *
     * @return Query
     */
    private function createQuery(string $dql = null): Query
    {
        $this->entityManagerMock
            ->method('getConfiguration')
            ->willReturn($this->configurationMock);

        $query = new Query($this->entityManagerMock);
        if (null !== $dql) {
            $query->setDQL($dql);
        }

        return $query;
    }

    /**
     * Create Query mock
     *
     * @param string[] $methods Methods to mock
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractQuery|Query
     */
    private function createQueryMock(string ...$methods)
    {
        return $this->getMockForAbstractClass(
            AbstractQuery::class,
            [],
            '',
            false,
            true,
            true,
            $methods
        );
    }

    /**
     * Create query mock that will return provided result
     *
     * @param mixed $result Expected result
     * @param int|null $maxResults getMaxResults method result
     *
     * @return AbstractQuery
     */
    private function createItemsQueryMock($result, int $maxResults = null): AbstractQuery
    {
        $queryMock = $this->createQueryMock('execute', 'getMaxResults');
        $queryMock->expects(static::once())
            ->method('execute')
            ->willReturn($result);
        $queryMock->method('getMaxResults')
            ->willReturn($maxResults);

        return $queryMock;
    }

    /**
     * Get value of object private property
     *
     * @param object $object Object
     * @param string $propertyName Property name
     *
     * @return mixed Property value
     */
    private function getObjectPrivatePropertyValue($object, string $propertyName)
    {
        $objectReflection = new \ReflectionObject($object);
        $propertyReflection = $objectReflection->getProperty($propertyName);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }
}
