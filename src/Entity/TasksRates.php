<?php

namespace App\Entity;

use App\Repository\TasksRatesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TasksRatesRepository::class)]
class TasksRates
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $taskRatesUserRole = null;

    #[ORM\Column]
    private ?int $taskRatesTask = null;

    #[ORM\Column]
    private ?int $taskRatesAmount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaskRatesUserRole(): ?int
    {
        return $this->taskRatesUserRole;
    }

    public function setTaskRatesUserRole(int $taskRatesUserRole): static
    {
        $this->taskRatesUserRole = $taskRatesUserRole;

        return $this;
    }

    public function getTaskRatesTask(): ?int
    {
        return $this->taskRatesTask;
    }

    public function setTaskRatesTask(int $taskRatesTask): static
    {
        $this->taskRatesTask = $taskRatesTask;

        return $this;
    }

    public function getTaskRatesAmount(): ?int
    {
        return $this->taskRatesAmount;
    }

    public function setTaskRatesAmount(int $taskRatesAmount): static
    {
        $this->taskRatesAmount = $taskRatesAmount;

        return $this;
    }
}
