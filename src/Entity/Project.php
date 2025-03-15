<?php

namespace App\Entity;

use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'project_name', length: 100)]
    #[Assert\NotBlank(message: 'Le nom du projet ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom du projet doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom du projet ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $projectName = null;

    #[ORM\Column(name: 'project_description', type: Types::TEXT, nullable: true)]
    private ?string $projectDescription = null;

    #[ORM\Column(name: 'project_status', type: 'string', length: 50)]
    private ?string $projectStatus = ProjectStatus::NEW->value;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(name: 'project_customer_id', referencedColumnName: 'id', nullable: false)]
    private ?Customers $projectCustomer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'project_manager_id', referencedColumnName: 'id', nullable: false)]
    private ?User $projectManager = null;

    #[ORM\Column(name: 'project_start_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $projectStartDate = null;

    #[ORM\Column(name: 'project_target_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $projectTargetDate = null;

    #[ORM\Column(name: 'project_end_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $projectEndDate = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'project_updated_by', referencedColumnName: 'id', nullable: false)]
    private ?User $projectUpdatedBy = null;

    #[ORM\Column(name: 'project_updated_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $projectUpdatedAt;

    #[ORM\OneToMany(mappedBy: 'taskProject', targetEntity: Tasks::class, orphanRemoval: true)]
    private Collection $tasks;

    public function __construct()
    {
        $this->projectUpdatedAt = new \DateTime();
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getProjectDescription(): ?string
    {
        return $this->projectDescription;
    }

    public function setProjectDescription(?string $projectDescription): static
    {
        $this->projectDescription = $projectDescription;
        return $this;
    }

    public function getProjectStatus(): ?ProjectStatus
    {
        return $this->projectStatus ? ProjectStatus::from($this->projectStatus) : null;
    }

    public function setProjectStatus(ProjectStatus $projectStatus): static
    {
        $this->projectStatus = $projectStatus->value;
        return $this;
    }

    public function getProjectCustomer(): ?Customers
    {
        return $this->projectCustomer;
    }

    public function setProjectCustomer(?Customers $projectCustomer): static
    {
        $this->projectCustomer = $projectCustomer;
        return $this;
    }

    public function getProjectManager(): ?User
    {
        return $this->projectManager;
    }

    public function setProjectManager(?User $projectManager): static
    {
        $this->projectManager = $projectManager;
        return $this;
    }

    public function getProjectStartDate(): ?\DateTimeInterface
    {
        return $this->projectStartDate;
    }

    public function setProjectStartDate(?\DateTimeInterface $projectStartDate): static
    {
        $this->projectStartDate = $projectStartDate;
        return $this;
    }

    public function getProjectTargetDate(): ?\DateTimeInterface
    {
        return $this->projectTargetDate;
    }

    public function setProjectTargetDate(?\DateTimeInterface $projectTargetDate): static
    {
        $this->projectTargetDate = $projectTargetDate;
        return $this;
    }

    public function getProjectEndDate(): ?\DateTimeInterface
    {
        return $this->projectEndDate;
    }

    public function setProjectEndDate(?\DateTimeInterface $projectEndDate): static
    {
        $this->projectEndDate = $projectEndDate;
        return $this;
    }

    public function getProjectUpdatedBy(): ?User
    {
        return $this->projectUpdatedBy;
    }

    public function setProjectUpdatedBy(?User $projectUpdatedBy): static
    {
        $this->projectUpdatedBy = $projectUpdatedBy;
        return $this;
    }

    public function getProjectUpdatedAt(): ?\DateTimeInterface
    {
        return $this->projectUpdatedAt;
    }

    public function setProjectUpdatedAt(\DateTimeInterface $projectUpdatedAt): static
    {
        $this->projectUpdatedAt = $projectUpdatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Tasks>
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Tasks $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setTaskProject($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): static
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getTaskProject() === $this) {
                $task->setTaskProject(null);
            }
        }

        return $this;
    }
} 