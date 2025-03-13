<?php

namespace App\Entity;

use App\Repository\TasksCommentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksCommentsRepository::class)]
class TasksComments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taskCommentsId = null;

    #[ORM\Column(length: 255)]
    private ?string $taskCommentsValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskCommentsId(): ?int
    {
        return $this->taskCommentsId;
    }

    public function setTaskCommentsId(int $taskCommentsId): static
    {
        $this->taskCommentsId = $taskCommentsId;

        return $this;
    }

    public function getTaskCommentsValue(): ?string
    {
        return $this->taskCommentsValue;
    }

    public function setTaskCommentsValue(string $taskCommentsValue): static
    {
        $this->taskCommentsValue = $taskCommentsValue;

        return $this;
    }
}
