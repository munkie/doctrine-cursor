<?php

declare(strict_types=1);

namespace Mnk\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Mnk\Cursor\AbstractCursor;

/**
 * Cursor that takes two native db queries
 * first to get items with applied limit and offset
 * second that will return total count
 */
class DoctrineOrmNativeQueryCursor extends AbstractCursor
{

    /**
     * Query that returns items
     * Should not have LIMIT OFFSET in sql, they will be added before execution
     *
     * @var NativeQuery
     */
    private $itemsQuery;

    /**
     * Query that return total count
     *
     * @var AbstractQuery
     */
    private $countQuery;

    /**
     * Original SQL from itemsQuery
     *
     * @var string
     */
    private $originalItemsSql;

    /**
     * @param AbstractQuery|NativeQuery $itemsQuery Items native db query
     * @param AbstractQuery $countQuery Items count query
     */
    public function __construct(NativeQuery $itemsQuery, AbstractQuery $countQuery)
    {
        $this->itemsQuery = $itemsQuery;
        $this->countQuery = $countQuery;
        $this->originalItemsSql = $itemsQuery->getSQL();
    }

    /**
     * {@inheritdoc}
     */
    protected function doIterate(): \Traversable
    {
        $limitOffsetSql = $this->itemsQuery
            ->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform()
            ->modifyLimitQuery($this->originalItemsSql, $this->limit, $this->offset);

        $this->itemsQuery->setSQL($limitOffsetSql);

        yield from $this->itemsQuery->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\NonUniqueResultException If count query returns more than one row
     */
    protected function doCount(): int
    {
        return (int) $this->countQuery->getSingleScalarResult();
    }
}
