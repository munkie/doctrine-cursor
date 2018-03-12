<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mnk\Cursor\CursorInterface;
use Mnk\Doctrine\DoctrineOrmQueryCursor;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;
use Mnk\Tests\Functional\Fixtures\Repository\MessageRepository;
use PHPUnit\Framework\TestCase;

/**
 * Functional tests for @see DoctrineOrmQueryCursor
 */
class DoctrineQueryTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var DebugStack
     */
    private $sqlLogger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createEntityManager();
        $this->reCreateSchema();
        $this->populateDb();
    }

    private function createEntityManager(): EntityManager
    {
        $paths = [__DIR__.'/Fixtures/Entity/'];
        $isDevMode = true;

        $dbParams = array(
            'driver'   => 'pdo_pgsql',
            'host' => 'postgres',
            'user'     => 'cursor',
            'password' => 'cursor',
            'dbname'   => 'cursor',
        );

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

        $this->sqlLogger = new DebugStack();
        $this->sqlLogger->enabled = false;
        $config->setSQLLogger($this->sqlLogger);

        return EntityManager::create($dbParams, $config);
    }

    private function reCreateSchema()
    {
        $classes = [
            $this->entityManager->getClassMetadata(Topic::class),
            $this->entityManager->getClassMetadata(Message::class)
        ];
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    private function populateDb()
    {
        $topic1 = new Topic(1, 'topic 1');
        $topic2 = new Topic(2, 'topic 2');

        $this->entityManager->persist($topic1);
        $this->entityManager->persist($topic2);

        for ($i = 10; $i < 30; $i++) {
            $message = new Message(
                $i,
                $topic1,
                'Body #'.$i,
                new \DateTime(sprintf('2018-01-29T21:%02d:%02d+04:00', $i, $i))
            );
            $this->entityManager->persist($message);
        }
        for ($i = 30; $i < 35; $i++) {
            $message = new Message(
                $i,
                $topic2,
                'Body #'.$i,
                new \DateTime(sprintf('2018-01-29T21:%02d:%02d+04:00', $i, $i))
            );
            $this->entityManager->persist($message);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testRepositoryCursorByQueryBuilder()
    {
        /** @var MessageRepository $repo */
        $repo = $this->entityManager->getRepository(Message::class);

        /** @var Topic $topic */
        $topic = $this->entityManager->find(Topic::class, 1);

        $this->sqlLogger->enabled = true;

        $cursor = $repo->findByTopic($topic);

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        static::assertSame([11, 12, 13], $this->flattenCursor($cursor), 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        static::assertSame([12, 13, 14], $this->flattenCursor($cursor), 'Incorrect result woth 3,2 limit,offset');

        static::assertCount(20, $cursor, 'Incorrect count result');

        $sqls = $this->getLoggedSqls();

        $expected = [
            'SELECT m0_.id AS id_0, m0_.body AS body_1, m0_.createdAt AS createdat_2, m0_.topic_id AS topic_id_3 FROM message m0_ WHERE m0_.topic_id = ? ORDER BY m0_.createdAt ASC, m0_.id ASC LIMIT 3 OFFSET 1',
            'SELECT m0_.id AS id_0, m0_.body AS body_1, m0_.createdAt AS createdat_2, m0_.topic_id AS topic_id_3 FROM message m0_ WHERE m0_.topic_id = ? ORDER BY m0_.createdAt ASC, m0_.id ASC LIMIT 3 OFFSET 2',
            'SELECT COUNT(1) AS sclr_0 FROM message m0_ WHERE m0_.topic_id = ?'
        ];
        static::assertEquals($expected, $sqls, 'Incorrect queries sqls were generated');
    }

    public function testRepositoryCursorByCriteria()
    {
        /** @var MessageRepository $repo */
        $repo = $this->entityManager->getRepository(Message::class);

        /** @var Topic $topic */
        $topic = $this->entityManager->find(Topic::class, 1);

        $this->sqlLogger->enabled = true;

        $cursor = $repo->findByTopicUsingCriteria($topic);

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        static::assertSame([11, 12, 13], $this->flattenCursor($cursor), 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        static::assertSame([12, 13, 14], $this->flattenCursor($cursor), 'Incorrect result woth 3,2 limit,offset');

        static::assertCount(20, $cursor, 'Incorrect count result');

        $sqls = $this->getLoggedSqls();

        $expected = [
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 WHERE t0.topic_id = ? ORDER BY t0.createdAt ASC, t0.id ASC LIMIT 3 OFFSET 1',
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 WHERE t0.topic_id = ? ORDER BY t0.createdAt ASC, t0.id ASC LIMIT 3 OFFSET 2',
            'SELECT COUNT(*) FROM message t0 WHERE t0.topic_id = ?'
        ];
        static::assertEquals($expected, $sqls, 'Incorrect queries sqls were generated');
    }

    public function testRepositoryCursorAll()
    {
        /** @var MessageRepository $repo */
        $repo = $this->entityManager->getRepository(Message::class);

        $this->sqlLogger->enabled = true;

        $cursor = $repo->findCursorAll();

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        static::assertSame([11, 12, 13], $this->flattenCursor($cursor), 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        static::assertSame([12, 13, 14], $this->flattenCursor($cursor), 'Incorrect result woth 3,2 limit,offset');

        static::assertCount(25, $cursor, 'Incorrect count result');

        $sqls = $this->getLoggedSqls();

        $expected = [
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 LIMIT 3 OFFSET 1',
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 LIMIT 3 OFFSET 2',
            'SELECT COUNT(*) FROM message t0'
        ];
        static::assertEquals($expected, $sqls, 'Incorrect queries sqls were generated');
    }

    public function testCustomCountQuery()
    {
        $itemsQuery = new Query($this->entityManager);
        $itemsQuery->setDQL('SELECT m FROM '.Message::class.' m');

        $countQuery = new Query($this->entityManager);
        // Lets use weird way to get count of messages in table
        $countQuery->setDQL('SELECT MAX(m.id) FROM '.Message::class.' m');

        $cursor = new DoctrineOrmQueryCursor($itemsQuery, $countQuery);
        static::assertCount(34, $cursor, 'Incorrect result of custom count query');
    }

    public function testZeroLimit()
    {
        $this->sqlLogger->enabled = true;

        $itemsQuery = new Query($this->entityManager);
        $itemsQuery->setDQL('SELECT m FROM '.Message::class.' m');

        $countQuery = new Query($this->entityManager);
        $countQuery->setDQL('SELECT COUNT(1) FROM '.Message::class.' m');

        $cursor = new DoctrineOrmQueryCursor($itemsQuery, $countQuery);
        $cursor->setLimit(0);

        static::assertSame([], $cursor->toArray());

        $sqls = $this->getLoggedSqls();

        static::assertSame([], $sqls, 'No queries should be executed when limit is 0');
    }

    public function testDistinctCount()
    {
        $this->sqlLogger->enabled = true;

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Message::class, 'm')
            ->select('m')
            ->where('m.topic = :topic')
            ->setParameter('topic', 1)
            ->orderBy('m.id', 'DESC');

        $cursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder, null, true);

        static::assertCount(20, $cursor, 'Incorrect result of count query');

        $sqls = $this->getLoggedSqls();

        $expected = [
            'SELECT COUNT(DISTINCT m0_.id) AS sclr_0 FROM message m0_ WHERE m0_.topic_id = ?'
        ];

        static::assertSame($expected, $sqls, 'Incorrect count query generated');
    }

    public function testDistinctQuery()
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Topic::class, 't')
            ->select('t')
            ->join('t.messages', 'm')
            ->where('m IN(:mids)')
            ->setParameter('mids', [10, 15, 20, 30])
            ->orderBy('t.id', 'DESC');

        $cursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder);

        static::assertSame([2, 1], $this->flattenCursor($cursor));
        static::assertCount(4, $cursor, 'Count should be 4 because joined messages table matched 4 times');

        $distinctCursor = DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder, null, true);
        static::assertCount(2, $distinctCursor, 'With distinct flag count should return 2 - number of distinct topics');
    }

    /**
     * Get ids of entities in cursor
     *
     * @param CursorInterface $cursor
     *
     * @return int[]
     */
    private function flattenCursor(CursorInterface $cursor): array
    {
        return array_map(
            function ($entity) {
                return $entity->getId();
            },
            $cursor->toArray()
        );
    }

    /**
     * Get queries sqls logged by sql logger
     *
     * @return string[]
     */
    private function getLoggedSqls(): array
    {
        $sqls = array_values(
            array_map(
                function (array $query) {
                    return $query['sql'];
                },
                $this->sqlLogger->queries
            )
        );
        return $sqls;
    }
}