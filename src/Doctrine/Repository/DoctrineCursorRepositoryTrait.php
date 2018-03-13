<?php

declare(strict_types=1);

namespace Mnk\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Mnk\Cursor\CursorInterface;
use Mnk\Doctrine\DoctrineOrmCriteriaCursor;
use Mnk\Doctrine\DoctrineOrmQueryCursor;

trait DoctrineCursorRepositoryTrait
{
    public function findCursorAll(): CursorInterface
    {
        return $this->findCursorBy([]);
    }

    public function findCursorBy(array $criteria, array $orderBy = null): CursorInterface
    {
        $persister = $this->getEntityManager()->getUnitOfWork()->getEntityPersister($this->getEntityName());

        return new DoctrineOrmCriteriaCursor($persister, $criteria, $orderBy);
    }

    protected function findCursorByQueryBuilder(QueryBuilder $queryBuilder): CursorInterface
    {
        return DoctrineOrmQueryCursor::fromQueryBuilder($queryBuilder);
    }

    /**
     * @see EntityRepository::getEntityManager()
     *
     * @return EntityManagerInterface
     */
    abstract protected function getEntityManager();

    /**
     * @see EntityRepository::getEntityName()
     *
     * @return string
     */
    abstract protected function getEntityName();
}