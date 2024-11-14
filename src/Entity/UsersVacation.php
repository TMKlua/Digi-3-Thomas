<?php

namespace App\Entity;

use App\Repository\UsersVacationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsersVacationRepository::class)]
class UsersVacation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $usersVacationUser = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usersVacationFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usersVacationTo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $usersDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usersDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $usersUserMaj = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsersVacationUser(): ?int
    {
        return $this->usersVacationUser;
    }

    public function setUsersVacationUser(int $usersVacationUser): static
    {
        $this->usersVacationUser = $usersVacationUser;

        return $this;
    }

    public function getUsersVacationFrom(): ?\DateTimeInterface
    {
        return $this->usersVacationFrom;
    }

    public function setUsersVacationFrom(?\DateTimeInterface $usersVacationFrom): static
    {
        $this->usersVacationFrom = $usersVacationFrom;

        return $this;
    }

    public function getUsersVacationTo(): ?\DateTimeInterface
    {
        return $this->usersVacationTo;
    }

    public function setUsersVacationTo(?\DateTimeInterface $usersVacationTo): static
    {
        $this->usersVacationTo = $usersVacationTo;

        return $this;
    }

    public function getUsersDateFrom(): ?\DateTimeInterface
    {
        return $this->usersDateFrom;
    }

    public function setUsersDateFrom(\DateTimeInterface $usersDateFrom): static
    {
        $this->usersDateFrom = $usersDateFrom;

        return $this;
    }

    public function getUsersDateTo(): ?\DateTimeInterface
    {
        return $this->usersDateTo;
    }

    public function setUsersDateTo(?\DateTimeInterface $usersDateTo): static
    {
        $this->usersDateTo = $usersDateTo;

        return $this;
    }

    public function getUsersUserMaj(): ?int
    {
        return $this->usersUserMaj;
    }

    public function setUsersUserMaj(?int $usersUserMaj): static
    {
        $this->usersUserMaj = $usersUserMaj;

        return $this;
    }
}
