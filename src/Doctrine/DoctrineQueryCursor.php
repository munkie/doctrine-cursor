<?php

declare(strict_types=1);

namespace Mnk\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mnk\Cursor\CursorInterface;

/**
 * Cursor that takes two queries: one to get items with applied limit and offset
 * And second that will return total count
 */
class DoctrineQueryCursor implements CursorInterface
{
    /**
     * Query that returns items
     * AbstractQuery is used only for testing purpose, because Query is final class and can't be mocked
     *
     * @var AbstractQuery|Query
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
     * @var int
     */
    private $count;

    /**
     * @param AbstractQuery|Query $itemsQuery Items query
     * @param AbstractQuery $countQuery Count query
     */
    public function __construct(AbstractQuery $itemsQuery, AbstractQuery $countQuery)
    {
        $this->itemsQuery = $itemsQuery;
        $this->countQuery = $countQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        // Do not execute query if limit is 0 it will return empty result anyway
        if (0 === $this->itemsQuery->getMaxResults()) {
            return new \ArrayIterator();
        }

        return new \ArrayIterator(
            // getResult() was forcing object hydrate mode, execute() will keep previously set hydration mode
            $this->itemsQuery->execute()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setLimit(?int $limit): void
    {
        $this->itemsQuery->setMaxResults($limit);
    }

    /**
     * {@inheritdoc}
     */
    public function setOffset(int $offset): void
    {
        $this->itemsQuery->setFirstResult($offset);
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

    /**
     * Create new query builder with count query
     *
     * @param QueryBuilder $queryBuilder Original query builder
     * @param bool $distinctCount Use COUNT(DISTINCT alias). False by default
     * @param string|null $fromAlias Alias. If null will try to get first root alias from query builder
     *
     * @return QueryBuilder Query builder with count query
     */
    public static function createCountQueryBuilder(
        QueryBuilder $queryBuilder,
        bool $distinctCount = false,
        string $fromAlias = null
    ): QueryBuilder
    {
        if ($distinctCount) {
            $fromAlias = $fromAlias ?? $queryBuilder->getRootAliases()[0];
            $select = "COUNT(DISTINCT {$fromAlias})";
        } else {
            // Dirty trick cause doctrine does not support COUNT(*)
            $select = 'COUNT(1)';
        }

        $countQueryBuilder = clone $queryBuilder;
        $countQueryBuilder->select($select);
        $countQueryBuilder->resetDQLPart('orderBy');

        return $countQueryBuilder;
    }

    /**
     * Create count query from items query builder
     *
     * @param QueryBuilder $queryBuilder Items query builder
     * @param AbstractQuery|null $query Custom item query
     * @param bool $distinctCount Use COUNT(DISTINCT alias). False by default
     * @param string|null $fromAlias From table alias for distinct
     *
     * @return static|self
     */
    public static function fromQueryBuilder(
        QueryBuilder $queryBuilder,
        AbstractQuery $query = null,
        bool $distinctCount = false,
        string $fromAlias = null
    ): self
    {
        $query = $query ?? $queryBuilder->getQuery();

        $countQuery = static::createCountQueryBuilder($queryBuilder, $distinctCount, $fromAlias)->getQuery();

        return new static($query, $countQuery);
    }
}
