<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    private ?string $userFirstName = null;

    #[ORM\Column(length: 35)]
    private ?string $userLastName = null;

    #[ORM\Column(length: 35, unique: true)]
    private ?string $userEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $userAvatar = 'img/account/default-avatar.jpg';

    #[ORM\Column(length: 35)] // ROLE_USER, ROLE_ADMIN, etc.
    private ?string $userRole = 'ROLE_USER';

    #[ORM\Column(length: 255)]
    private ?string $userPassword = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $userUserMaj = null;

    public function getEmail(): ?string
{
    return $this->userEmail;
}
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

    // Implémentation de la méthode getUserIdentifier de l'interface UserInterface
    public function getUserIdentifier(): string
    {
        return $this->userEmail;  // Retourne l'email comme identifiant unique
    }

    // Méthodes requises par PasswordAuthenticatedUserInterface
    public function getPassword(): ?string
    {
        return $this->userPassword;
    }

    // Méthodes requises par UserInterface
    public function getUsername(): string
    {
        return $this->userEmail; // Utilisation de l'email comme identifiant
    }

    public function getRoles(): array
    {
        return [$this->userRole]; // Retourne le rôle de l'utilisateur
    }

    public function setPassword(string $password): static
    {
        $this->userPassword = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si des données sensibles temporaires sont stockées, elles doivent être effacées ici.
    }
}
