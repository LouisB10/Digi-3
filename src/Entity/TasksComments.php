<?php

namespace App\Entity;

use App\Repository\TasksCommentsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TasksCommentsRepository::class)]
#[ORM\Table(name: 'task_comments')]
class TasksComments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tasks::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'comment_task_id', referencedColumnName: 'id', nullable: false)]
    private ?Tasks $task = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'comment_user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'comment_content', type: 'text')]
    #[Assert\NotBlank(message: 'Le contenu du commentaire ne peut pas être vide.')]
    private string $content;

    #[ORM\Column(name: 'comment_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): ?Tasks
    {
        return $this->task;
    }

    public function setTask(?Tasks $task): static
    {
        $this->task = $task;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
} 