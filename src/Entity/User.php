<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Enum\UserRole;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['userEmail'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_RESPONSABLE = 'ROLE_RESPONSABLE';
    public const ROLE_PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    public const ROLE_LEAD_DEVELOPER = 'ROLE_LEAD_DEVELOPER';
    public const ROLE_DEVELOPER = 'ROLE_DEVELOPER';
    public const ROLE_USER = 'ROLE_USER';

    public const VALID_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_RESPONSABLE,
        self::ROLE_PROJECT_MANAGER,
        self::ROLE_LEAD_DEVELOPER,
        self::ROLE_DEVELOPER,
        self::ROLE_USER
    ];

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
    #[Assert\Email(
        message: 'L\'email {{ value }} n\'est pas valide.',
        mode: 'strict'
    )]
    private ?string $userEmail = null;

    #[ORM\Column(length: 255)]
    private string $userAvatar = '/img/account/default-avatar.jpg';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.')]
    private ?string $userPassword = null;

    #[ORM\Column(length: 20, enumType: UserRole::class)]
    private UserRole $userRole = UserRole::USER;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $userCreatedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userUpdatedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'user_updated_by', referencedColumnName: 'id', nullable: true)]
    private ?self $userUpdatedBy = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    public function __construct()
    {
        $this->userCreatedAt = new \DateTime();
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

    public function getUserAvatar(): string
    {
        return $this->userAvatar;
    }

    public function setUserAvatar(string $userAvatar): static
    {
        $this->userAvatar = $userAvatar;
        return $this;
    }

    public function getUserCreatedAt(): \DateTimeInterface
    {
        return $this->userCreatedAt;
    }

    public function getUserUpdatedAt(): ?\DateTimeInterface
    {
        return $this->userUpdatedAt;
    }

    public function setUserUpdatedAt(?\DateTimeInterface $userUpdatedAt): static
    {
        $this->userUpdatedAt = $userUpdatedAt;
        return $this;
    }

    public function getUserUpdatedBy(): ?self
    {
        return $this->userUpdatedBy;
    }

    public function setUserUpdatedBy(?self $userUpdatedBy): static
    {
        $this->userUpdatedBy = $userUpdatedBy;
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

    public function getUserIdentifier(): string
    {
        return $this->userEmail;
    }

    public function getPassword(): string
    {
        return $this->userPassword;
    }

    public function getRoles(): array
    {
        return [$this->userRole->value];
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles
    }

    public function setPassword(string $hashedPassword): self
    {
        $this->userPassword = $hashedPassword;
        return $this;
    }

    public function getUserRole(): UserRole
    {
        return $this->userRole;
    }

    public function setUserRole(UserRole $userRole): self
    {
        $this->userRole = $userRole;
        return $this;
    }

    public static function create(
        UserPasswordHasherInterface $passwordHasher,
        string $firstName,
        string $lastName,
        string $email,
        string $plainPassword,
        UserRole $role = UserRole::USER
    ): self {
        $user = new self();
        $user->setUserFirstName($firstName);
        $user->setUserLastName($lastName);
        $user->setUserEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setUserRole($role);
        return $user;
    }
}
