<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\TaskStatus;
use App\Enum\TaskPriority;
use App\Enum\TaskComplexity;

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

    #[ORM\Column(length: 20, enumType: TaskStatus::class)]
    private TaskStatus $taskStatus = TaskStatus::NEW;

    #[ORM\Column(length: 20, enumType: TaskPriority::class)]
    #[ORM\Column(name: 'task_priority', type: 'string', enumType: TaskPriority::class)]
    private TaskPriority $taskPriority = TaskPriority::MEDIUM;

    #[ORM\Column(name: 'task_complexity', type: 'string', enumType: TaskComplexity::class, nullable: true)]
    #[Assert\Choice(choices: self::VALID_COMPLEXITY, message: 'Complexité de tâche invalide.')]
    private ?TaskComplexity $taskComplexity = null;

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

    public function getTaskStatus(): TaskStatus
    {
        return $this->taskStatus;
    }

    public function setTaskStatus(TaskStatus $taskStatus): static
    {
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

    public function getTaskPriority(): TaskPriority
    {
        return $this->taskPriority;
    }

    public function setTaskPriority(TaskPriority $taskPriority): static
    {
        $this->taskPriority = $taskPriority;
        return $this;
    }

    public function getTaskComplexity(): ?TaskComplexity
    {
        return $this->taskComplexity;
    }

    public function setTaskComplexity(?TaskComplexity $taskComplexity): static
    {
        $this->taskComplexity = $taskComplexity;
        return $this;
    }
}
