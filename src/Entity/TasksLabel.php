<?php

namespace App\Entity;

use App\Repository\TasksLabelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksLabelRepository::class)]
class TasksLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taskLabelId = null;

    #[ORM\Column(length: 255)]
    private ?string $taskLabelValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskLabelId(): ?int
    {
        return $this->taskLabelId;
    }

    public function setTaskLabelId(int $taskLabelId): static
    {
        $this->taskLabelId = $taskLabelId;

        return $this;
    }

    public function getTaskLabelValue(): ?string
    {
        return $this->taskLabelValue;
    }

    public function setTaskLabelValue(string $taskLabelValue): static
    {
        $this->taskLabelValue = $taskLabelValue;

        return $this;
    }
}
