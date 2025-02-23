<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\ProjectStatus;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'projects')]
class Project
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const VALID_STATUS = [
        self::STATUS_DRAFT,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'project_name', length: 255)]
    #[Assert\NotBlank(message: 'Le nom du projet ne peut pas être vide.')]
    private ?string $projectName = null;

    #[ORM\Column(name: 'project_description', type: 'text', nullable: true)]
    private ?string $projectDescription = null;

    #[ORM\Column(type: 'string', enumType: ProjectStatus::class)]
    private ProjectStatus $projectStatus = ProjectStatus::NEW;

    #[ORM\ManyToOne(targetEntity: Customers::class)]
    #[ORM\JoinColumn(name: 'project_customer_id', referencedColumnName: 'id', nullable: false)]
    private ?Customers $projectCustomer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'project_manager_id', referencedColumnName: 'id', nullable: false)]
    private ?User $projectManager = null;

    #[ORM\Column(name: 'project_start_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est requise.')]
    private \DateTimeInterface $projectStartDate;

    #[ORM\Column(name: 'project_end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $projectEndDate = null;

    #[ORM\Column(name: 'project_target_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date cible est requise.')]
    #[Assert\GreaterThan(propertyPath: "projectStartDate", message: "La date cible doit être postérieure à la date de début")]
    private \DateTimeInterface $projectTargetDate;

    #[ORM\Column(name: 'project_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $projectCreatedAt;

    #[ORM\Column(name: 'project_updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $projectUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'project_updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $projectUpdatedBy = null;

    #[ORM\OneToMany(mappedBy: 'taskProject', targetEntity: Tasks::class, cascade: ['persist', 'remove'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
        $this->projectCreatedAt = new \DateTime();
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

    public function getProjectStatus(): ProjectStatus
    {
        return $this->projectStatus;
    }

    public function setProjectStatus(ProjectStatus $projectStatus): static
    {
        $this->projectStatus = $projectStatus;
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

    public function getProjectStartDate(): \DateTimeInterface
    {
        return $this->projectStartDate;
    }

    public function setProjectStartDate(\DateTimeInterface $projectStartDate): static
    {
        $this->projectStartDate = $projectStartDate;
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

    public function getProjectTargetDate(): \DateTimeInterface
    {
        return $this->projectTargetDate;
    }

    public function setProjectTargetDate(\DateTimeInterface $projectTargetDate): static
    {
        $this->projectTargetDate = $projectTargetDate;
        return $this;
    }

    public function getProjectCreatedAt(): \DateTimeInterface
    {
        return $this->projectCreatedAt;
    }

    public function getProjectUpdatedAt(): ?\DateTimeInterface
    {
        return $this->projectUpdatedAt;
    }

    public function setProjectUpdatedAt(?\DateTimeInterface $projectUpdatedAt): static
    {
        $this->projectUpdatedAt = $projectUpdatedAt;
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
            if ($task->getTaskProject() === $this) {
                $task->setTaskProject(null);
            }
        }
        return $this;
    }
}
