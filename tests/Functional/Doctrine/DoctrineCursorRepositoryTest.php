<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Doctrine;

use Mnk\Cursor\CursorInterface;
use Mnk\Tests\Functional\BaseDoctrineTestCase;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;
use Mnk\Tests\Functional\Fixtures\Repository\MessageRepository;

class DoctrineCursorRepositoryTest extends BaseDoctrineTestCase
{
    /**
     * @var MessageRepository
     */
    private $repo;

    protected function setUp()
    {
        parent::setUp();

        $this->repo = $this->entityManager->getRepository(Message::class);
    }

    public function testRepositoryCursorByQueryBuilder()
    {
        /** @var Topic $topic */
        $topic = $this->entityManager->find(Topic::class, 1);

        $this->sqlLogger->enabled = true;

        $cursor = $this->repo->findByTopic($topic);

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        $this->assertCursor([11, 12, 13], $cursor, 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        $this->assertCursor([12, 13, 14], $cursor, 'Incorrect result with 3,2 limit,offset');

        static::assertCount(20, $cursor, 'Incorrect count result');

        $expected = [
            'SELECT m0_.id AS id_0, m0_.body AS body_1, m0_.createdAt AS createdat_2, m0_.topic_id AS topic_id_3 FROM message m0_ WHERE m0_.topic_id = ? ORDER BY m0_.createdAt ASC, m0_.id ASC LIMIT 3 OFFSET 1',
            'SELECT m0_.id AS id_0, m0_.body AS body_1, m0_.createdAt AS createdat_2, m0_.topic_id AS topic_id_3 FROM message m0_ WHERE m0_.topic_id = ? ORDER BY m0_.createdAt ASC, m0_.id ASC LIMIT 3 OFFSET 2',
            'SELECT COUNT(1) AS sclr_0 FROM message m0_ WHERE m0_.topic_id = ?'
        ];
        $this->assertLoggedSqls($expected);
    }

    public function testRepositoryCursorByCriteria()
    {
        /** @var Topic $topic */
        $topic = $this->entityManager->find(Topic::class, 1);

        $this->sqlLogger->enabled = true;

        $cursor = $this->repo->findByTopicUsingCriteria($topic);

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        $this->assertCursor([11, 12, 13], $cursor, 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        $this->assertCursor([12, 13, 14], $cursor, 'Incorrect result with 3,2 limit,offset');

        static::assertCount(20, $cursor, 'Incorrect count result');

        $expected = [
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 WHERE t0.topic_id = ? ORDER BY t0.createdAt ASC, t0.id ASC LIMIT 3 OFFSET 1',
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 WHERE t0.topic_id = ? ORDER BY t0.createdAt ASC, t0.id ASC LIMIT 3 OFFSET 2',
            'SELECT COUNT(*) FROM message t0 WHERE t0.topic_id = ?'
        ];
        $this->assertLoggedSqls($expected);
    }

    public function testRepositoryCursorAll()
    {
        $this->sqlLogger->enabled = true;

        $cursor = $this->repo->findCursorAll();

        static::assertInstanceOf(CursorInterface::class, $cursor);

        $cursor->setLimit(3);
        $cursor->setOffset(1);

        $this->assertCursor([11, 12, 13], $cursor, 'Incorrect result with 3,1 limit,offset');

        $cursor->setOffset(2);

        $this->assertCursor([12, 13, 14], $cursor, 'Incorrect result with 3,2 limit,offset');

        static::assertCount(25, $cursor, 'Incorrect count result');

        $expected = [
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 LIMIT 3 OFFSET 1',
            'SELECT t0.id AS id_1, t0.body AS body_2, t0.createdAt AS createdat_3, t0.topic_id AS topic_id_4 FROM message t0 LIMIT 3 OFFSET 2',
            'SELECT COUNT(*) FROM message t0'
        ];
        $this->assertLoggedSqls($expected);
    }
}