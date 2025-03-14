<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

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

    #[ORM\Column(length: 35)]
    private ?string $taskType = null;

    #[ORM\Column(length: 35)]
    private ?string $taskName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $taskDescription = null;    

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $taskStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $taskParent = null;

    #[ORM\Column(nullable: true)]
    private ?int $taskUser = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $taskRank = 1;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskRealStartDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskRealEndDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskTargetStartDate = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $taskComplexity = null;

    #[ORM\Column(length: 35, nullable: true)]
    private ?string $taskPriority = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskTargetEndDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $taskDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $taskUserMaj = null;

    #[ORM\ManyToOne(targetEntity: ManagerProject::class, inversedBy: "tasks")]
    #[ORM\JoinColumn(nullable: false)]
    private ?ManagerProject $project = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $taskCategory = null; // Catégorie de la tâche

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $taskAttachments = null;

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

    public function getTaskRank(): ?int
    {
        return $this->taskRank;
    }

    public function setTaskRanks(int $taskRank): self
    {
        $this->taskRank = $taskRank;

        return $this;
    }

    public function getTaskStatus(): ?string
    {
        return $this->taskStatus;
    }

    public function setTaskStatus(?string $taskStatus): self
    {
        $this->taskStatus = $taskStatus;
        return $this;
    }

    public function getTaskCategory(): ?string
    {
        return $this->taskCategory;
    }

    public function setTaskCategory(?string $taskCategory): self
    {
        $this->taskCategory = $taskCategory;
        return $this;
    }

    public function getTaskAttachments(): ?array
    {
        return $this->taskAttachments;
    }

    public function setTaskAttachments(?array $taskAttachments): self
    {
        $this->taskAttachments = $taskAttachments;
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

    public function getTaskParent(): ?int
    {
        return $this->taskParent;
    }

    public function setTaskParent(?int $taskParent): static
    {
        $this->taskParent = $taskParent;

        return $this;
    }

    public function getTaskUser(): ?int
    {
        return $this->taskUser;
    }

    public function setTaskUser(?int $taskUser): static
    {
        $this->taskUser = $taskUser;

        return $this;
    }

    public function getTaskRealStartDate(): ?\DateTimeInterface
    {
        return $this->taskRealStartDate;
    }

    public function setTaskRealStartDate(?\DateTimeInterface $taskRealStartDate): static
    {
        $this->taskRealStartDate = $taskRealStartDate;

        return $this;
    }

    public function getTaskRealEndDate(): ?\DateTimeInterface
    {
        return $this->taskRealEndDate;
    }

    public function setTaskRealEndDate(?\DateTimeInterface $taskRealEndDate): static
    {
        $this->taskRealEndDate = $taskRealEndDate;

        return $this;
    }

    public function getTaskTargetStartDate(): ?\DateTimeInterface
    {
        return $this->taskTargetStartDate;
    }

    public function setTaskTargetStartDate(?\DateTimeInterface $taskTargetStartDate): static
    {
        $this->taskTargetStartDate = $taskTargetStartDate;

        return $this;
    }

    public function getTaskComplexity(): ?string
    {
        return $this->taskComplexity;
    }

    public function setTaskComplexity(?string $taskComplexity): static
    {
        $this->taskComplexity = $taskComplexity;

        return $this;
    }

    public function getTaskPriority(): ?string
    {
        return $this->taskPriority;
    }

    public function setTaskPriority(?string $taskPriority): static
    {
        $this->taskPriority = $taskPriority;

        return $this;
    }

    public function getTaskTargetEndDate(): ?\DateTimeInterface
    {
        return $this->taskTargetEndDate;
    }

    public function setTaskTargetEndDate(?\DateTimeInterface $taskTargetEndDate): static
    {
        $this->taskTargetEndDate = $taskTargetEndDate;

        return $this;
    }

    public function getTaskDateFrom(): ?\DateTimeInterface
    {
        return $this->taskDateFrom;
    }

    public function setTaskDateFrom(?\DateTimeInterface $taskDateFrom): static
    {
        $this->taskDateFrom = $taskDateFrom;

        return $this;
    }

    public function getTaskDateTo(): ?\DateTimeInterface
    {
        return $this->taskDateTo;
    }

    public function setTaskDateTo(?\DateTimeInterface $taskDateTo): static
    {
        $this->taskDateTo = $taskDateTo;

        return $this;
    }

    public function getTaskUserMaj(): ?int
    {
        return $this->taskUserMaj;
    }

    public function setTaskUserMaj(?int $taskUserMaj): static
    {
        $this->taskUserMaj = $taskUserMaj;

        return $this;
    }

    public function getProject(): ?ManagerProject
    {
        return $this->project;
    }

    public function setProject(?ManagerProject $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getTaskType(): ?string
    {
        return $this->taskType;
    }

    public function setTaskType(?string $taskType): self
    {
        if (!in_array($taskType, $this->getAllowedTaskTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Invalid task type: %s', $taskType));
        }

        $this->taskType = $taskType;

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
