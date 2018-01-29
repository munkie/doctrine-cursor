<?php

declare(strict_types=1);

namespace Mnk\Tests\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Mnk\Doctrine\DoctrineNativeQueryCursor;
use PHPUnit\Framework\TestCase;

/**
 * Test @see DoctrineNativeQueryCursor
 */
class DoctrineNativeQueryCursorTest extends TestCase
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
     * DBAL connection mock
     *
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->configurationMock = $this->createMock(Configuration::class);
        $this->connectionMock = $this->createMock(Connection::class);

        $this->entityManagerMock
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->entityManagerMock
            ->method('getConfiguration')
            ->willReturn($this->configurationMock);

        $this->configurationMock
            ->method('getDefaultQueryHints')
            ->willReturn([]);

        $this->connectionMock
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSqlPlatform());
    }

    public function testHydrationModeFromQueryIsUsedOnItemsQueryExecution()
    {
        $itemsQuery = $this->createNativeQuery('SELECT * FROM table');
        $itemsQuery->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $countQuery = $this->createNativeQuery();

        $cursor = new DoctrineNativeQueryCursor(
            $itemsQuery,
            $countQuery
        );

        $expectedResult = [[1], [2]];
        $this->setExpectedHydrationResult($expectedResult, AbstractQuery::HYDRATE_ARRAY);

        $result = iterator_to_array($cursor);

        static::assertSame($expectedResult, $result, 'Incorrect hydration result returned');
    }

    /**
     * Test cursor as iterator
     */
    public function testIterator()
    {
        $result = [1, 2, 3];

        $sql = 'SELECT * FROM table';
        $cursor = new DoctrineNativeQueryCursor(
            $this->createNativeQuery($sql),
            $this->createNativeQuery()
        );

        $this->connectionMock->expects(static::once())
            ->method('executeQuery')
            ->with('SELECT * FROM table OFFSET 0');

        $this->setExpectedHydrationResult($result);

        static::assertSame($result, iterator_to_array($cursor), 'Iterator values are incorrect');
    }

    /**
     * Test db query sql with different limit offset values
     *
     * @dataProvider limitOffsetSqlProvider
     *
     * @param string $sql Original native query sql
     * @param int|null $limit Applied limit
     * @param int $offset Applied offset
     * @param string $expectedSql Generated SQL
     */
    public function testIteratorDbQueryLimitOffset(string $sql, ?int $limit, int $offset, string $expectedSql)
    {
        $cursor = new DoctrineNativeQueryCursor(
            $this->createNativeQuery($sql),
            $this->createNativeQuery()
        );

        $cursor->setLimit($limit);
        $cursor->setOffset($offset);

        $this->connectionMock->expects(static::once())
            ->method('executeQuery')
            ->with($expectedSql);

        $this->setExpectedHydrationResult();

        iterator_to_array($cursor);
    }

    /**
     * Data provider for @see testIteratorDbQueryLimitOffset
     * @return array
     */
    public static function limitOffsetSqlProvider(): array
    {
        return [
            '0 offset, no limit' => [
                'sql' => 'SELECT * FROM table',
                'limit' => null,
                'offset' => 0,
                'expected sql' => 'SELECT * FROM table OFFSET 0',
            ],
            '0 offset, 10 limit' => [
                'sql' => 'SELECT * FROM table',
                'limit' => 10,
                'offset' => 0,
                'expected sql' => 'SELECT * FROM table LIMIT 10 OFFSET 0',
            ],
            '10 offset, 10 limit' => [
                'sql' => 'SELECT * FROM table',
                'limit' => 10,
                'offset' => 10,
                'expected sql' => 'SELECT * FROM table LIMIT 10 OFFSET 10',
            ],
        ];
    }

    /**
     * Test db query is not executed when limit is zero
     */
    public function testQueryIsNotExecutedWhenLimitIsZero()
    {
        $sql = 'SELECT * FROM table';
        $cursor = new DoctrineNativeQueryCursor(
            $this->createNativeQuery($sql),
            $this->createNativeQuery()
        );

        $this->connectionMock->expects(static::never())
            ->method('executeQuery')
            ->with($sql);

        $cursor->setLimit(0);

        static::assertInstanceOf(\Traversable::class, $cursor, 'Should return Traversable');
        static::assertSame([], iterator_to_array($cursor), 'Iterator values are incorrect');
    }

    /**
     * Test toArray method
     */
    public function testToArray()
    {
        $result = [1, 2, 3];

        $itemsQuery = $this->createNativeQuery();
        $countQuery = $this->createNativeQuery();
        $cursor = new DoctrineNativeQueryCursor($itemsQuery, $countQuery);

        $this->setExpectedHydrationResult($result);

        static::assertSame($result, $cursor->toArray(), 'Incorrect getItems result, should be array');
    }

    /**
     * Test count() method
     */
    public function testCount()
    {
        $count = 42;
        $countSql = 'SELECT COUNT(*) FROM table';

        $itemsQuery = $this->createNativeQuery();
        $countQuery = $this->createNativeQuery($countSql);

        $this->connectionMock->expects(static::once())
            ->method('executeQuery')
            ->with($countSql)
            ->willReturn($count);

        $cursor = new DoctrineNativeQueryCursor($itemsQuery, $countQuery);

        static::assertCount($count, $cursor, 'Incorrect count returned');
    }

    /**
     * Test count() method is called more then once will trigger only one db query
     */
    public function testCountQueryIsPerformedOnce()
    {
        $count = 42;
        $countSql = 'SELECT COUNT(*) FROM table';

        $itemsQuery = $this->createNativeQuery();
        $countQuery = $this->createNativeQuery($countSql);

        $this->connectionMock->expects(static::once())
            ->method('executeQuery')
            ->with($countSql)
            ->willReturn($count);

        $cursor = new DoctrineNativeQueryCursor($itemsQuery, $countQuery);

        static::assertCount($count, $cursor, 'Incorrect count returned for first time');
        static::assertCount($count, $cursor, 'Incorrect count returned for second time');
    }

    /**
     * Create native query with mocked entity manager
     *
     * @param string|null $sql Query SQL
     *
     * @return NativeQuery
     */
    private function createNativeQuery(string $sql = null): NativeQuery
    {
        $query = new NativeQuery($this->entityManagerMock);
        if (null !== $sql) {
            $query->setSQL($sql);
        }

        return $query;
    }

    /**
     * Set expected result of query hydration
     *
     * @param array $hydrationResult Hydration result
     * @param int|string $hydrationMode Hydration mode
     *
     * @return AbstractHydrator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function setExpectedHydrationResult(
        array $hydrationResult = [],
        $hydrationMode = AbstractQuery::HYDRATE_OBJECT
    ): AbstractHydrator
    {

        $hydratorMock = $this->createMock(AbstractHydrator::class);
        $hydratorMock
            ->expects(static::atLeastOnce())
            ->method('hydrateAll')
            ->willReturn($hydrationResult);

        $this->entityManagerMock
            ->expects(static::atLeastOnce())
            ->method('newHydrator')
            ->with($hydrationMode)
            ->willReturn($hydratorMock);

        return $hydratorMock;
    }
}
