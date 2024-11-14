<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $userFirstName = null;

    #[ORM\Column(length: 35)]
    private ?string $userLastName = null;

    #[ORM\Column(length: 35)]
    private ?string $userEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $userAvatar = null;

    #[ORM\Column(length: 35)]
    private ?string $userRole = null;

    #[ORM\Column(length: 255)]
    private ?string $userPassword = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $userUserMaj = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserFirstName(): ?string
    {
        return $this->userFirstName;
    }

    public function setUserFirstName(string $userFirstName): static
    {
        $this->userFirstName = $userFirstName;

        return $this;
    }

    public function getUserLastName(): ?string
    {
        return $this->userLastName;
    }

    public function setUserLastName(string $userLastName): static
    {
        $this->userLastName = $userLastName;

        return $this;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(string $userEmail): static
    {
        $this->userEmail = $userEmail;

        return $this;
    }

    public function getUserAvatar(): ?string
    {
        return $this->userAvatar;
    }

    public function setUserAvatar(string $userAvatar): static
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    public function getUserRole(): ?string
    {
        return $this->userRole;
    }

    public function setUserRole(string $userRole): static
    {
        $this->userRole = $userRole;

        return $this;
    }

    public function getUserPassword(): ?string
    {
        return $this->userPassword;
    }

    public function setUserPassword(string $userPassword): static
    {
        $this->userPassword = $userPassword;

        return $this;
    }

    public function getUserDateFrom(): ?\DateTimeInterface
    {
        return $this->userDateFrom;
    }

    public function setUserDateFrom(?\DateTimeInterface $userDateFrom): static
    {
        $this->userDateFrom = $userDateFrom;

        return $this;
    }

    public function getUserDateTo(): ?\DateTimeInterface
    {
        return $this->userDateTo;
    }

    public function setUserDateTo(?\DateTimeInterface $userDateTo): static
    {
        $this->userDateTo = $userDateTo;

        return $this;
    }

    public function getUserUserMaj(): ?int
    {
        return $this->userUserMaj;
    }

    public function setUserUserMaj(?int $userUserMaj): static
    {
        $this->userUserMaj = $userUserMaj;

        return $this;
    }
}
