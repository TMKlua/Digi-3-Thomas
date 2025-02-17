<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TasksRepository::class)]
#[ORM\Table(name: 'tasks')]
class Tasks
{
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    public const COMPLEXITY_LOW = 'low';
    public const COMPLEXITY_MEDIUM = 'medium';
    public const COMPLEXITY_HIGH = 'high';

    public const VALID_STATUS = [
        self::STATUS_TODO,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED
    ];

    public const VALID_PRIORITY = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH
    ];

    public const VALID_COMPLEXITY = [
        self::COMPLEXITY_LOW,
        self::COMPLEXITY_MEDIUM,
        self::COMPLEXITY_HIGH
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'task_name', length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la tâche ne peut pas être vide.')]
    private ?string $taskName = null;

    #[ORM\Column(name: 'task_description', type: 'text', nullable: true)]
    private ?string $taskDescription = null;

    #[ORM\Column(name: 'task_status', type: 'string', enumType: 'string')]
    #[Assert\Choice(choices: self::VALID_STATUS, message: 'Statut de tâche invalide.')]
    private string $taskStatus = self::STATUS_TODO;

    #[ORM\Column(name: 'task_priority', type: 'string', enumType: 'string', nullable: true)]
    #[Assert\Choice(choices: self::VALID_PRIORITY, message: 'Priorité de tâche invalide.')]
    private ?string $taskPriority = self::PRIORITY_MEDIUM;

    #[ORM\Column(name: 'task_complexity', type: 'string', enumType: 'string', nullable: true)]
    #[Assert\Choice(choices: self::VALID_COMPLEXITY, message: 'Complexité de tâche invalide.')]
    private ?string $taskComplexity = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(name: 'task_project_id', referencedColumnName: 'id', nullable: false)]
    private ?Project $taskProject = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'task_assigned_to', referencedColumnName: 'id', nullable: true)]
    private ?User $taskAssignedTo = null;

    #[ORM\Column(name: 'task_start_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskStartDate = null;

    #[ORM\Column(name: 'task_end_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskEndDate = null;

    #[ORM\Column(name: 'task_target_date', type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date cible est requise.')]
    private \DateTimeInterface $taskTargetDate;

    #[ORM\Column(name: 'task_created_at', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $taskCreatedAt;

    #[ORM\Column(name: 'task_updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'task_updated_by', referencedColumnName: 'id', nullable: true)]
    private ?User $taskUpdatedBy = null;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TasksComments::class, cascade: ['persist', 'remove'])]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TasksAttachments::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->taskCreatedAt = new \DateTime();
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

    public function getTaskStatus(): ?string
    {
        return $this->taskStatus;
    }

    public function setTaskStatus(?string $taskStatus): self
    {
        if (!in_array($taskStatus, self::VALID_STATUS, true)) {
            throw new \InvalidArgumentException('Statut de tâche invalide.');
        }
        $this->taskStatus = $taskStatus;
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

    public function setTaskTargetDate(\DateTimeInterface $taskTargetDate): static
    {
        $this->taskTargetDate = $taskTargetDate;

        return $this;
    }

    public function getTaskCreatedAt(): ?\DateTimeInterface
    {
        return $this->taskCreatedAt;
    }

    public function getTaskUpdatedAt(): ?\DateTimeInterface
    {
        return $this->taskUpdatedAt;
    }

    public function setTaskUpdatedAt(?\DateTimeInterface $taskUpdatedAt): static
    {
        $this->taskUpdatedAt = $taskUpdatedAt;

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

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function getTaskPriority(): ?string
    {
        return $this->taskPriority;
    }

    public function setTaskPriority(?string $taskPriority): static
    {
        if ($taskPriority !== null && !in_array($taskPriority, self::VALID_PRIORITY, true)) {
            throw new \InvalidArgumentException('Priorité de tâche invalide.');
        }
        $this->taskPriority = $taskPriority;
        return $this;
    }

    public function getTaskComplexity(): ?string
    {
        return $this->taskComplexity;
    }

    public function setTaskComplexity(?string $taskComplexity): static
    {
        if ($taskComplexity !== null && !in_array($taskComplexity, self::VALID_COMPLEXITY, true)) {
            throw new \InvalidArgumentException('Complexité de tâche invalide.');
        }
        $this->taskComplexity = $taskComplexity;
        return $this;
    }
}
