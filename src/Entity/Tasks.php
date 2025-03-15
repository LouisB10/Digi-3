<?php

namespace App\Entity;

use App\Enum\TaskComplexity;
use App\Enum\TaskPriority;
use App\Enum\TaskStatus;
use App\Repository\TasksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: TasksRepository::class)]
class Tasks
{
    public const TASK_TYPE_BUG = 'Bug';
    public const TASK_TYPE_FEATURE = 'Feature';
    public const TASK_TYPE_HIGHTEST = 'Hightest';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'task_type', length: 35)]
    private ?string $taskType = null;

    #[ORM\Column(length: 100)]
    private ?string $taskName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $taskDescription = null;

    #[ORM\Column(name: 'task_status', type: 'string', length: 50)]
    private ?string $taskStatus = TaskStatus::NEW->value;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $taskProject = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $taskAssignedTo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskStartDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskEndDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskTargetDate = null;

    #[ORM\Column(name: 'task_complexity', type: 'string', length: 50)]
    private ?string $taskComplexity = TaskComplexity::MODERATE->value;

    #[ORM\Column(name: 'task_priority', type: 'string', length: 50)]
    private ?string $taskPriority = TaskPriority::MEDIUM->value;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $taskUpdatedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $taskUpdatedAt;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TasksComments::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TasksAttachments::class, orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        $this->taskUpdatedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(string $taskName): static
    {
        $this->taskName = $taskName;
        return $this;
    }

    public function getTaskStatus(): ?TaskStatus
    {
        return $this->taskStatus ? TaskStatus::from($this->taskStatus) : null;
    }

    public function setTaskStatus(TaskStatus $taskStatus): static
    {
        $this->taskStatus = $taskStatus->value;
        return $this;
    }

    public function getTaskDescription(): ?string
    {
        return $this->taskDescription;
    }

    public function setTaskDescription(string $taskDescription): static
    {
        $this->taskDescription = $taskDescription;
        return $this;
    }

    public function getTaskProject(): ?Project
    {
        return $this->taskProject;
    }

    public function setTaskProject(?Project $taskProject): static
    {
        $this->taskProject = $taskProject;
        return $this;
    }

    public function getTaskAssignedTo(): ?User
    {
        return $this->taskAssignedTo;
    }

    public function setTaskAssignedTo(?User $taskAssignedTo): static
    {
        $this->taskAssignedTo = $taskAssignedTo;
        return $this;
    }

    public function getTaskStartDate(): ?\DateTimeInterface
    {
        return $this->taskStartDate;
    }

    public function setTaskStartDate(?\DateTimeInterface $taskStartDate): static
    {
        $this->taskStartDate = $taskStartDate;
        return $this;
    }

    public function getTaskEndDate(): ?\DateTimeInterface
    {
        return $this->taskEndDate;
    }

    public function setTaskEndDate(?\DateTimeInterface $taskEndDate): static
    {
        $this->taskEndDate = $taskEndDate;
        return $this;
    }

    public function getTaskTargetDate(): ?\DateTimeInterface
    {
        return $this->taskTargetDate;
    }

    public function setTaskTargetDate(?\DateTimeInterface $taskTargetDate): static
    {
        $this->taskTargetDate = $taskTargetDate;
        return $this;
    }

    public function getTaskComplexity(): ?TaskComplexity
    {
        return $this->taskComplexity ? TaskComplexity::from($this->taskComplexity) : null;
    }

    public function setTaskComplexity(TaskComplexity $taskComplexity): static
    {
        $this->taskComplexity = $taskComplexity->value;
        return $this;
    }

    public function getTaskPriority(): ?TaskPriority
    {
        return $this->taskPriority ? TaskPriority::from($this->taskPriority) : null;
    }

    public function setTaskPriority(TaskPriority $taskPriority): static
    {
        $this->taskPriority = $taskPriority->value;
        return $this;
    }

    public function getTaskUpdatedBy(): ?User
    {
        return $this->taskUpdatedBy;
    }

    public function setTaskUpdatedBy(?User $taskUpdatedBy): static
    {
        $this->taskUpdatedBy = $taskUpdatedBy;
        return $this;
    }

    public function getTaskUpdatedAt(): ?\DateTimeInterface
    {
        return $this->taskUpdatedAt;
    }

    public function setTaskUpdatedAt(\DateTimeInterface $taskUpdatedAt): static
    {
        $this->taskUpdatedAt = $taskUpdatedAt;
        return $this;
    }

    public function getTaskType(): ?string
    {
        return $this->taskType;
    }

    public function setTaskType(string $taskType): static
    {
        $this->taskType = $taskType;
        return $this;
    }

    /**
     * @return Collection<int, TasksComments>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(TasksComments $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }
        return $this;
    }

    public function removeComment(TasksComments $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getTask() === $this) {
                $comment->setTask(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, TasksAttachments>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(TasksAttachments $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setTask($this);
        }
        return $this;
    }

    public function removeAttachment(TasksAttachments $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getTask() === $this) {
                $attachment->setTask(null);
            }
        }
        return $this;
    }

    public static function getAllowedTaskTypes(): array
    {
        return [
            self::TASK_TYPE_BUG,
            self::TASK_TYPE_FEATURE,
            self::TASK_TYPE_HIGHTEST,
        ];
    }
}
