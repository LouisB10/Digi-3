<?php

namespace App\Entity;

use App\Repository\CustomersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomersRepository::class)]
#[ORM\Table(name: 'customers')]
class Customers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'customer_name', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du client ne peut pas être vide.')]
    private ?string $customerName = null;

    #[ORM\Column(name: 'customer_address_street', length: 255, nullable: true)]
    private ?string $customerAddressStreet = null;

    #[ORM\Column(name: 'customer_address_zipcode', length: 35, nullable: true)]
    private ?string $customerAddressZipcode = null;

    #[ORM\Column(name: 'customer_address_city', length: 255, nullable: true)]
    private ?string $customerAddressCity = null;

    #[ORM\Column(name: 'customer_address_country', length: 35, nullable: true)]
    private ?string $customerAddressCountry = null;

    #[ORM\Column(name: 'customer_vat', length: 35, nullable: true)]
    private ?string $customerVAT = null;

    #[ORM\Column(name: 'customer_siren', length: 35, nullable: true)]
    private ?string $customerSIREN = null;

    #[ORM\Column(name: 'customer_reference', length: 255, nullable: true)]
    private ?string $customerReference = null;

    #[ORM\Column(name: 'customer_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $customerCreatedAt;

    #[ORM\Column(name: 'customer_updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $customerUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'customer_updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $customerUpdatedBy = null;

    #[ORM\OneToMany(mappedBy: 'projectCustomer', targetEntity: Project::class)]
    private Collection $projects;

    public function __construct()
    {
        $this->customerCreatedAt = new \DateTime();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerAddressStreet(): ?string
    {
        return $this->customerAddressStreet;
    }

    public function setCustomerAddressStreet(?string $customerAddressStreet): static
    {
        $this->customerAddressStreet = $customerAddressStreet;
        return $this;
    }

    public function getCustomerAddressZipcode(): ?string
    {
        return $this->customerAddressZipcode;
    }

    public function setCustomerAddressZipcode(?string $customerAddressZipcode): static
    {
        $this->customerAddressZipcode = $customerAddressZipcode;
        return $this;
    }

    public function getCustomerAddressCity(): ?string
    {
        return $this->customerAddressCity;
    }

    public function setCustomerAddressCity(?string $customerAddressCity): static
    {
        $this->customerAddressCity = $customerAddressCity;
        return $this;
    }

    public function getCustomerAddressCountry(): ?string
    {
        return $this->customerAddressCountry;
    }

    public function setCustomerAddressCountry(?string $customerAddressCountry): static
    {
        $this->customerAddressCountry = $customerAddressCountry;
        return $this;
    }

    public function getCustomerVAT(): ?string
    {
        return $this->customerVAT;
    }

    public function setCustomerVAT(?string $customerVAT): static
    {
        $this->customerVAT = $customerVAT;
        return $this;
    }

    public function getCustomerSIREN(): ?string
    {
        return $this->customerSIREN;
    }

    public function setCustomerSIREN(?string $customerSIREN): static
    {
        $this->customerSIREN = $customerSIREN;
        return $this;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setCustomerReference(?string $customerReference): static
    {
        $this->customerReference = $customerReference;
        return $this;
    }

    public function getCustomerCreatedAt(): \DateTimeInterface
    {
        return $this->customerCreatedAt;
    }

    public function getCustomerUpdatedAt(): ?\DateTimeInterface
    {
        return $this->customerUpdatedAt;
    }

    public function setCustomerUpdatedAt(?\DateTimeInterface $customerUpdatedAt): static
    {
        $this->customerUpdatedAt = $customerUpdatedAt;
        return $this;
    }

    public function getCustomerUpdatedBy(): ?User
    {
        return $this->customerUpdatedBy;
    }

    public function setCustomerUpdatedBy(?User $customerUpdatedBy): static
    {
        $this->customerUpdatedBy = $customerUpdatedBy;
        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setProjectCustomer($this);
        }
        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            if ($project->getProjectCustomer() === $this) {
                $project->setProjectCustomer(null);
            }
        }
        return $this;
    }
}
