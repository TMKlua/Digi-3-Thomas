<?php

namespace App\Entity;

use App\Repository\ParameterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParameterRepository::class)]
class Parameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $paramKey = null;

    #[ORM\Column(length: 35)]
    private ?string $paramValue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $paramDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $paramDateTo = null;

    #[ORM\Column(length: 35)]
    private ?string $paramUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getParamKey(): ?string
    {
        return $this->paramKey;  
    }

    public function setParamKey(string $paramKey): static
    {
        $this->paramKey = $paramKey;

        return $this;
    }

    public function getParamValue(): ?string
    {
        return $this->paramValue;
    }

    public function setParamValue(string $paramValue): static
    {
        $this->paramValue = $paramValue;

        return $this;
    }

        public function getParamDateFrom(): ?\DateTimeInterface
    {
        return $this->paramDateFrom;
    }

    public function setParamDateFrom(\DateTimeInterface $paramDateFrom): static
    {
        $this->paramDateFrom = $paramDateFrom;

        return $this;
    }

    public function getParamDateTo(): ?\DateTimeInterface
    {
        return $this->paramDateTo;
    }

    public function setParamDateTo(\DateTimeInterface $paramDateTo): static
    {
        $this->paramDateTo = $paramDateTo;

        return $this;
    }


    public function getParamUser(): ?string
    {
        return $this->paramUser;
    }

    public function setParamUser(string $paramUser): static
    {
        $this->paramUser = $paramUser;

        return $this;
    }
}
