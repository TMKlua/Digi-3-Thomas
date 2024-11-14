<?php

namespace App\Entity;

use App\Repository\TasksRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksRepository::class)]
class Tasks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $taskType = null;

    #[ORM\Column(length: 35)]
    private ?string $taskName = null;

    #[ORM\Column(length: 255)]
    private ?string $taskText = null;

    #[ORM\Column(nullable: true)]
    private ?int $taskParent = null;

    #[ORM\Column(nullable: true)]
    private ?int $taskUser = null;

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

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTaskName(): ?string
    {
        return $this->taskName;
    }

    public function setTaskName(string $taskName): static
    {
        $this->taskName = $taskName;

        return $this;
    }

    public function getTaskText(): ?string
    {
        return $this->taskText;
    }

    public function setTaskText(string $taskText): static
    {
        $this->taskText = $taskText;

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
}
