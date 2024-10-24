<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $start_date_forecast = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $end_date_forecast = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $validity_date_from = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $validity_date_to = null;

    #[ORM\Column(length: 255)]
    private ?string $project_leader_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

        return $this;
    }

    public function getStartDateForecast(): ?\DateTimeInterface
    {
        return $this->start_date_forecast;
    }

    public function setStartDateForecast(\DateTimeInterface $start_date_forecast): static
    {
        $this->start_date_forecast = $start_date_forecast;

        return $this;
    }

    public function getEndDateForecast(): ?\DateTimeInterface
    {
        return $this->end_date_forecast;
    }

    public function setEndDateForecast(\DateTimeInterface $end_date_forecast): static
    {
        $this->end_date_forecast = $end_date_forecast;

        return $this;
    }

    public function getValidityDateFrom(): ?\DateTimeInterface
    {
        return $this->validity_date_from;
    }

    public function setValidityDateFrom(\DateTimeInterface $validity_date_from): static
    {
        $this->validity_date_from = $validity_date_from;

        return $this;
    }

    public function getValidityDateTo(): ?\DateTimeInterface
    {
        return $this->validity_date_to;
    }

    public function setValidityDateTo(\DateTimeInterface $validity_date_to): static
    {
        $this->validity_date_to = $validity_date_to;

        return $this;
    }

    public function getProjectLeaderId(): ?string
    {
        return $this->project_leader_id;
    }

    public function setProjectLeaderId(string $project_leader_id): static
    {
        $this->project_leader_id = $project_leader_id;

        return $this;
    }
}
