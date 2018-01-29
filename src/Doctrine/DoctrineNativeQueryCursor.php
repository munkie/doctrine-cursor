<?php

declare(strict_types=1);

namespace Mnk\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NativeQuery;
use Mnk\Cursor\CursorInterface;

/**
 * Cursor that takes two native db queries
 * first to get items with applied limit and offset
 * second that will return total count
 */
class DoctrineNativeQueryCursor implements CursorInterface
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
     * Count query result
     *
     * @var int|null
     */
    private $count;

    /**
     * Limit value for items query
     *
     * @var int|null
     */
    private $limit;

    /**
     * Offset value for items query
     *
     * @var int
     */
    private $offset = 0;

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
    public function getIterator(): \Traversable
    {
        // Do not execute db query if limit is 0 it will return empty result anyway
        if (0 === $this->limit) {
            return new \ArrayIterator();
        }

        $limitOffsetSql = $this->itemsQuery
            ->getEntityManager()
            ->getConnection()
            ->getDatabasePlatform()
            ->modifyLimitQuery($this->originalItemsSql, $this->limit, $this->offset);

        $this->itemsQuery->setSQL($limitOffsetSql);

        return new \ArrayIterator(
            $this->itemsQuery->execute()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ORM\NoResultException If count query returns 0 rows
     * @throws \Doctrine\ORM\NonUniqueResultException If count query returns more than one row
     */
    public function count(): int
    {
        if (null === $this->count) {
            $this->count = (int) $this->countQuery->getSingleScalarResult();
        }

        return $this->count;
    }

}
