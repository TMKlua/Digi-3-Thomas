<?php

namespace App\Entity;

use App\Repository\ParameterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParameterRepository::class)]
class Parameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    #[Assert\NotBlank(message: "Le champ paramKey ne doit pas être vide.")]
    #[Assert\Length(max: 35, maxMessage: "Le paramKey ne doit pas dépasser {{ limit }} caractères.")]
    private ?string $paramKey = null;

    #[ORM\Column(length: 35)]
    #[Assert\NotBlank(message: "Le champ paramValue ne doit pas être vide.")]
    #[Assert\Length(max: 35, maxMessage: "Le paramValue ne doit pas dépasser {{ limit }} caractères.")]
    private ?string $paramValue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "Le champ paramDateFrom ne doit pas être vide.")]
    private ?\DateTimeInterface $paramDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: "Le champ paramDateTo ne doit pas être vide.")]
    private ?\DateTimeInterface $paramDateTo = null;



    // Getters and Setters
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

}
