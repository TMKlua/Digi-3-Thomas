<?php

namespace App\Entity;

use App\Repository\TaskWorkloadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskWorkloadRepository::class)]
class TaskWorkload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taskWorkloadTask = null;

    #[ORM\Column]
    private ?int $taskWorkloadUser = null;

    #[ORM\Column]
    private ?int $taskWorkloadDuration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $taskWorkloadDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $taskWorkloadDateTo = null;

    #[ORM\Column]
    private ?int $taskWorkloadUserMaj = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskWorkloadTask(): ?int
    {
        return $this->taskWorkloadTask;
    }

    public function setTaskWorkloadTask(int $taskWorkloadTask): static
    {
        $this->taskWorkloadTask = $taskWorkloadTask;

        return $this;
    }

    public function getTaskWorkloadUser(): ?int
    {
        return $this->taskWorkloadUser;
    }

    public function setTaskWorkloadUser(int $taskWorkloadUser): static
    {
        $this->taskWorkloadUser = $taskWorkloadUser;

        return $this;
    }

    public function getTaskWorkloadDuration(): ?int
    {
        return $this->taskWorkloadDuration;
    }

    public function setTaskWorkloadDuration(int $taskWorkloadDuration): static
    {
        $this->taskWorkloadDuration = $taskWorkloadDuration;

        return $this;
    }

    public function getTaskWorkloadDateFrom(): ?\DateTimeInterface
    {
        return $this->taskWorkloadDateFrom;
    }

    public function setTaskWorkloadDateFrom(\DateTimeInterface $taskWorkloadDateFrom): static
    {
        $this->taskWorkloadDateFrom = $taskWorkloadDateFrom;

        return $this;
    }

    public function getTaskWorkloadDateTo(): ?\DateTimeInterface
    {
        return $this->taskWorkloadDateTo;
    }

    public function setTaskWorkloadDateTo(\DateTimeInterface $taskWorkloadDateTo): static
    {
        $this->taskWorkloadDateTo = $taskWorkloadDateTo;

        return $this;
    }

    public function getTaskWorkloadUserMaj(): ?int
    {
        return $this->taskWorkloadUserMaj;
    }

    public function setTaskWorkloadUserMaj(int $taskWorkloadUserMaj): static
    {
        $this->taskWorkloadUserMaj = $taskWorkloadUserMaj;

        return $this;
    }
}
