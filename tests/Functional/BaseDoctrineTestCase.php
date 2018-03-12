<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Mnk\Cursor\CursorInterface;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;
use PHPUnit\Framework\TestCase;

abstract class BaseDoctrineTestCase extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var DebugStack
     */
    protected $sqlLogger;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->createEntityManager();
        $this->sqlLogger = $this->entityManager->getConfiguration()->getSQLLogger();

        $this->reCreateSchema($this->entityManager);
        $this->populateDb($this->entityManager);
    }

    protected function createEntityManager(array $dbParams = []): EntityManagerInterface
    {
        $paths = [__DIR__.'/Fixtures/Entity/'];
        $isDevMode = true;

        $dbParams += [
            'driver'   => 'pdo_pgsql',
            'host' => 'postgres',
            'user'     => 'cursor',
            'password' => 'cursor',
            'dbname'   => 'cursor',
        ];

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

        $sqlLogger = new DebugStack();
        $sqlLogger->enabled = false;
        $config->setSQLLogger($sqlLogger);

        return EntityManager::create($dbParams, $config);
    }

    protected function reCreateSchema(EntityManagerInterface $entityManager)
    {
        $classes = [
            $entityManager->getClassMetadata(Topic::class),
            $entityManager->getClassMetadata(Message::class)
        ];
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    protected function populateDb(EntityManagerInterface $entityManager)
    {
        $topic1 = new Topic(1, 'topic 1');
        $topic2 = new Topic(2, 'topic 2');

        $entityManager->persist($topic1);
        $entityManager->persist($topic2);

        for ($i = 10; $i < 30; $i++) {
            $message = new Message(
                $i,
                $topic1,
                'Body #'.$i,
                new \DateTime(sprintf('2018-01-29T21:%02d:%02d+04:00', $i, $i))
            );
            $entityManager->persist($message);
        }
        for ($i = 30; $i < 35; $i++) {
            $message = new Message(
                $i,
                $topic2,
                'Body #'.$i,
                new \DateTime(sprintf('2018-01-29T21:%02d:%02d+04:00', $i, $i))
            );
            $entityManager->persist($message);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    protected function assertCursor(iterable $expectedItems, CursorInterface $cursor, string $message = null)
    {
        $message = $message ?? 'Cursor returned incorrect items';
        static::assertSame($expectedItems, $this->flattenCursor($cursor), $message);
    }

    /**
     * Get ids of entities in cursor
     *
     * @param CursorInterface $cursor
     *
     * @return int[]
     */
    protected function flattenCursor(CursorInterface $cursor): array
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
    protected function getLoggedSqls(): array
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

    /**
     * @param array $expectedSqls Expected SQLs to be genrated
     * @param string|null $message Custom assertion message
     */
    protected function assertLoggedSqls(array $expectedSqls, string $message = null)
    {
        $message = $message ?? 'Incorrect queries sqls were generated';
        $sqls = $this->getLoggedSqls();

        static::assertEquals($expectedSqls, $sqls, $message);
    }
}