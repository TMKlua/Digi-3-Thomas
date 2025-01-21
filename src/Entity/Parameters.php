<?php

namespace App\Entity;

use App\Repository\ParametersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParametersRepository::class)]
class Parameters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    #[Assert\NotBlank(message: "La clé du paramètre ne peut pas être vide")]
    #[Assert\Length(
        min: 3, 
        max: 35, 
        minMessage: "La clé doit faire au moins {{ limit }} caractères",
        maxMessage: "La clé ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $paramKey = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La valeur du paramètre ne peut pas être vide")]
    private ?string $paramValue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de début est obligatoire")]
    private ?\DateTimeInterface $paramDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: "La date de fin est obligatoire")]
    #[Assert\GreaterThan(propertyPath: "paramDateFrom", message: "La date de fin doit être postérieure à la date de début")]
    private ?\DateTimeInterface $paramDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $paramUserMaj = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function setParamDateFrom(?\DateTimeInterface $paramDateFrom): static
    {
        $this->paramDateFrom = $paramDateFrom;

        return $this;
    }

    public function getParamDateTo(): ?\DateTimeInterface
    {
        return $this->paramDateTo;
    }

    public function setParamDateTo(?\DateTimeInterface $paramDateTo): static
    {
        $this->paramDateTo = $paramDateTo;

        return $this;
    }

    public function getParamUserMaj(): ?int
    {
        return $this->paramUserMaj;
    }

    public function setParamUserMaj(?int $paramUserMaj): static
    {
        $this->paramUserMaj = $paramUserMaj;

        return $this;
    }

    public function isActive(): bool
    {
        $now = new \DateTime();
        return $now >= $this->paramDateFrom && $now <= $this->paramDateTo;
    }

    public function extractCategory(): ?string
    {
        $parts = explode('_', $this->paramKey);
        return count($parts) > 1 ? $parts[0] : null;
    }
}
