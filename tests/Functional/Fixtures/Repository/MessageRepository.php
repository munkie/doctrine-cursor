<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Fixtures\Repository;

use Doctrine\ORM\EntityRepository;
use Mnk\Cursor\CursorInterface;
use Mnk\Doctrine\Repository\DoctrineCursorRepository;
use Mnk\Doctrine\DoctrineOrmQueryCursor;
use Mnk\Tests\Functional\Fixtures\Entity\Message;
use Mnk\Tests\Functional\Fixtures\Entity\Topic;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 */
class MessageRepository extends DoctrineCursorRepository
{
    /**
     * @param Topic $topic
     * @return CursorInterface|Message[]
     */
    public function findByTopic(Topic $topic): CursorInterface
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $queryBuilder->where('m.topic = :topic');
        $queryBuilder->setParameter('topic', $topic);
        $queryBuilder->orderBy('m.createdAt', 'ASC');
        $queryBuilder->addOrderBy('m.id', 'ASC');

        return $this->findCursorByQueryBuilder($queryBuilder);
    }

    public function findByTopicUsingCriteria(Topic $topic): CursorInterface
    {
        return $this->findCursorBy(['topic' => $topic], ['createdAt' => 'ASC', 'id' => 'ASC']);
    }
}
