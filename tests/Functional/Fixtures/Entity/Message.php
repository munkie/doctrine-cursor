<?php

declare(strict_types=1);

namespace Mnk\Tests\Functional\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity(repositoryClass="Mnk\Tests\Functional\Fixtures\Repository\MessageRepository")
 * @Table(name="message")
 */
class Message
{
    /**
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @Column(type="integer")
     *
     * @var int
     */
    protected $id;

    /**
     * @Column(type="text")
     * @var string
     */
    protected $body;

    /**
     * @ManyToOne(targetEntity=Topic::class, inversedBy="messages")
     *
     * @var Topic
     */
    protected $topic;

    /**
     * @Column(type="datetime")
     * @var \DateTimeInterface
     */
    protected $createdAt;

    public function __construct(int $id, Topic $topic, string $body, \DateTimeInterface $createdAt)
    {
        $this->id = $id;
        $this->body = $body;
        $this->topic = $topic;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTopic(): Topic
    {
        return $this->topic;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}