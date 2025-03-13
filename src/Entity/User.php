<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 35)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 35,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $userFirstName = null;

    #[ORM\Column(length: 35)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide.')]
    #[Assert\Length(
        min: 2,
        max: 35,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $userLastName = null;

    #[ORM\Column(length: 35, unique: true)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    private ?string $userEmail = null;

    #[ORM\Column(length: 255)]
    private string $userAvatar = '/img/account/default-avatar.jpg';

    #[ORM\Column(length: 35)]
    private string $userRole = 'ROLE_USER';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&].{8,}$/',
        message: 'Le mot de passe doit contenir au moins une lettre, un chiffre et un caractère spécial.'
    )]
    private ?string $userPassword = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $userUserMaj = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

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

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->userEmail;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->userPassword;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->userRole) {
            $roles[] = $this->userRole;
        }
        return array_unique($roles);
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier() instead
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setPassword(string $password): self
    {
        $this->userPassword = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires
    }

    public static function create(
        string $firstName,
        string $lastName,
        string $email,
        string $hashedPassword
    ): self {
        $user = new self();
        $user
            ->setUserFirstName($firstName)
            ->setUserLastName($lastName)
            ->setUserEmail($email)
            ->setPassword($hashedPassword)
            ->setUserDateFrom(new \DateTime())
            ->setUserAvatar('/img/account/default-avatar.jpg')
            ->setUserRole('ROLE_USER')
            ->setResetToken(null)
            ->setResetTokenExpiresAt(null)
            ->setUserDateTo(null)
            ->setUserUserMaj(null);

        return $user;
    }
}
