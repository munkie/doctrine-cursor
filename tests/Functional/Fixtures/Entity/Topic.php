<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * @Entity()
 * @Table(name="topic")
 */
class Topic
{
    /**
     * @Id()
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     *
     * @var int
     */
    protected $id;

    /**
     * @Column(type="text")
     *
     * @var string
     */
    protected $title;

    /**
     * @OneToMany(targetEntity=Message::class, mappedBy="topic", fetch="EXTRA_LAZY")
     * @var Collection|Message[]
     */
    protected $messages;

    public function __construct(int $id, string $title)
    {
        $this->messages = new ArrayCollection();
        $this->id = $id;
        $this->title = $title;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }
}