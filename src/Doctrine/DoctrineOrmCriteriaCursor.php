<?php

declare(strict_types=1);

namespace Mnk\Doctrine;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Mnk\Cursor\AbstractCursor;

class DoctrineOrmCriteriaCursor extends AbstractCursor
{
    /**
     * @var EntityPersister
     */
    private $persister;

    /**
     * @var array
     */
    private $criteria;

    /**
     * @var array
     */
    private $orderBy;

    /**
     * DoctrineOrmCriteriaCursor constructor.
     *
     * @param EntityPersister $persister
     * @param array $criteria
     * @param array|null $orderBy
     */
    public function __construct(EntityPersister $persister, array $criteria = [], array $orderBy = null)
    {
        $this->persister = $persister;
        $this->criteria = $criteria;
        $this->orderBy = $orderBy;
    }

    protected function doCount(): int
    {
        return (int) $this->persister->count($this->criteria);
    }

    protected function doIterate(): \Traversable
    {
        yield from $this->persister->loadAll($this->criteria, $this->orderBy, $this->limit, $this->offset);
    }
}