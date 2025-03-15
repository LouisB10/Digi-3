<?php

namespace App\Entity;

use App\Repository\TasksAttachmentsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TasksAttachmentsRepository::class)]
#[ORM\Table(name: 'task_attachments')]
class TasksAttachments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tasks::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'attachment_task_id', referencedColumnName: 'id', nullable: false)]
    private ?Tasks $task = null;

    #[ORM\Column(name: 'attachment_name', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du fichier ne peut pas être vide.')]
    private string $name;

    #[ORM\Column(name: 'attachment_original_name', length: 255)]
    private string $originalName;

    #[ORM\Column(name: 'attachment_file_size')]
    private int $fileSize;

    #[ORM\Column(name: 'attachment_mime_type', length: 100)]
    private string $mimeType;

    #[ORM\Column(name: 'attachment_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'attachment_uploaded_by', referencedColumnName: 'id', nullable: false)]
    private ?User $uploadedBy = null;

    #[ORM\Column(name: 'attachment_created_at', type: Types::DATETIME_MUTABLE)]
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->name;
    }

    public function setFileName(string $fileName): static
    {
        return $this->setName($fileName);
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        return $this;
    }

    public function getOriginalFileName(): string
    {
        return $this->originalName;
    }

    public function setOriginalFileName(string $originalFileName): static
    {
        return $this->setOriginalName($originalFileName);
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): static
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
} 