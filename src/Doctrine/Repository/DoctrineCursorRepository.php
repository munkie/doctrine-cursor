<?php

declare(strict_types=1);

namespace Mnk\Doctrine\Repository;

use Doctrine\ORM\EntityRepository;

class DoctrineCursorRepository extends EntityRepository
{
    use DoctrineCursorRepositoryTrait;
}