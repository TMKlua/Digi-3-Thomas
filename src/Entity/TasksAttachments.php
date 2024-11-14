<?php

namespace App\Entity;

use App\Repository\TasksAttachmentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksAttachmentsRepository::class)]
class TasksAttachments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taskAttachmentsId = null;

    #[ORM\Column(length: 255)]
    private ?string $taskAttachmentsValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskAttachmentsId(): ?int
    {
        return $this->taskAttachmentsId;
    }

    public function setTaskAttachmentsId(int $taskAttachmentsId): static
    {
        $this->taskAttachmentsId = $taskAttachmentsId;

        return $this;
    }

    public function getTaskAttachmentsValue(): ?string
    {
        return $this->taskAttachmentsValue;
    }

    public function setTaskAttachmentsValue(string $taskAttachmentsValue): static
    {
        $this->taskAttachmentsValue = $taskAttachmentsValue;

        return $this;
    }
}
