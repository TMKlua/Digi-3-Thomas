<?php

namespace App\Entity;

use App\Repository\ManagerProjectRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManagerProjectRepository::class)]
class ManagerProject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date_forecast = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date_forecast = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $projectLeader = null;

    #[ORM\OneToMany(mappedBy: 'project', targetEntity: Tasks::class, cascade: ['persist', 'remove'])]
    private Collection $tasks;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getStartDateForecast(): ?\DateTimeInterface
    {
        return $this->start_date_forecast;
    }

    public function setStartDateForecast(?\DateTimeInterface $start_date_forecast): static
    {
        $this->start_date_forecast = $start_date_forecast;

        return $this;
    }

    public function getEndDateForecast(): ?\DateTimeInterface
    {
        return $this->end_date_forecast;
    }

    public function setEndDateForecast(?\DateTimeInterface $end_date_forecast): static
    {
        $this->end_date_forecast = $end_date_forecast;

        return $this;
    }

    public function getProjectLeader(): ?User
    {
        return $this->projectLeader;
    }
    
    public function setProjectLeader(?User $projectLeader): self
    {
        $this->projectLeader = $projectLeader;
    
        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Tasks $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setProject($this);
        }

        return $this;
    }

    public function removeTask(Tasks $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // Définir le projet sur null (s'il est défini)
            if ($task->getProject() === $this) {
                $task->setProject(null);
            }
        }

        return $this;
    }
}
