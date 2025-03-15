<?php

namespace App\Entity;

use App\Repository\ParametersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParametersRepository::class)]
#[ORM\Table(name: 'parameters')]
class Parameters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'param_key', length: 50, unique: true)]
    #[Assert\NotBlank(message: "La clé du paramètre ne peut pas être vide")]
    #[Assert\Length(
        min: 3, 
        max: 50, 
        minMessage: "La clé doit faire au moins {{ limit }} caractères",
        maxMessage: "La clé ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $paramKey = null;

    #[ORM\Column(name: 'param_value', type: 'text')]
    #[Assert\NotBlank(message: "La valeur du paramètre ne peut pas être vide")]
    private ?string $paramValue = null;

    #[ORM\Column(name: 'param_description', type: 'text', nullable: true)]
    private ?string $paramDescription = null;

    #[ORM\Column(name: 'param_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $paramCreatedAt;

    #[ORM\Column(name: 'param_updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paramUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'param_updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $paramUpdatedBy = null;

    public function __construct()
    {
        $this->paramCreatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParamKey(): ?string
    {
        return $this->paramKey;
    }

    public function setParamKey(string $paramKey): static
    {
        $this->paramKey = $paramKey;
        return $this;
    }

    public function getParamValue(): ?string
    {
        return $this->paramValue;
    }

    public function setParamValue(string $paramValue): static
    {
        $this->paramValue = $paramValue;
        return $this;
    }

    public function getParamDescription(): ?string
    {
        return $this->paramDescription;
    }

    public function setParamDescription(?string $paramDescription): static
    {
        $this->paramDescription = $paramDescription;
        return $this;
    }

    public function getParamCreatedAt(): \DateTimeInterface
    {
        return $this->paramCreatedAt;
    }

    public function getParamUpdatedAt(): ?\DateTimeInterface
    {
        return $this->paramUpdatedAt;
    }

    public function setParamUpdatedAt(?\DateTimeInterface $paramUpdatedAt): static
    {
        $this->paramUpdatedAt = $paramUpdatedAt;
        return $this;
    }

    public function getParamUpdatedBy(): ?User
    {
        return $this->paramUpdatedBy;
    }

    public function setParamUpdatedBy(?User $paramUpdatedBy): static
    {
        $this->paramUpdatedBy = $paramUpdatedBy;
        return $this;
    }

    public function extractCategory(): ?string
    {
        if (!$this->paramKey) {
            return null;
        }
        $parts = explode('_', $this->paramKey);
        return count($parts) > 1 ? $parts[0] : null;
    }
}
