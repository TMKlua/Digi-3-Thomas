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

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['userEmail'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_DEVELOPER = 'ROLE_DEVELOPER';
    public const ROLE_LEAD_DEVELOPER = 'ROLE_LEAD_DEVELOPER';
    public const ROLE_PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    public const ROLE_RESPONSABLE = 'ROLE_RESPONSABLE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const VALID_ROLES = [
        self::ROLE_USER,
        self::ROLE_DEVELOPER,
        self::ROLE_LEAD_DEVELOPER,
        self::ROLE_PROJECT_MANAGER,
        self::ROLE_RESPONSABLE,
        self::ROLE_ADMIN
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
    #[Assert\NotBlank]
    private string $userAvatar = '/img/account/default-avatar.jpg';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide.')]
    private ?string $userPassword = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $userDateFrom;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $userDateTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $userUserMaj = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(
        choices: self::VALID_ROLES,
        message: 'Le rôle sélectionné n\'est pas valide.'
    )]
    private string $userRole = self::ROLE_USER;

    public function __construct()
    {
        $this->userDateFrom = new \DateTime();
    }

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
        return $this->userEmail;
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
        $roles = [$this->userRole];
        
        // Garantit que tous les utilisateurs ont au moins ROLE_USER
        if (!in_array(self::ROLE_USER, $roles, true)) {
            $roles[] = self::ROLE_USER;
        }

        return array_unique($roles);
    }

    /**
     * @deprecated 
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function setPassword(string $hashedPassword): self
    {
        $this->userPassword = $hashedPassword;
        return $this;
    }

    public function resetPassword(UserPasswordHasherInterface $passwordHasher, string $plainPassword): self
    {
        $hashedPassword = $passwordHasher->hashPassword($this, $plainPassword);
        $this->userPassword = $hashedPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires
        // $this->plainPassword = null;
    }

    public static function create(
        UserPasswordHasherInterface $passwordHasher,
        string $firstName,
        string $lastName,
        string $email,
        string $plainPassword,
        string $role = self::ROLE_USER
    ): self {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException('Rôle invalide fourni.');
        }

        $user = new self();
        
        try {
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            
            $user
                ->setUserFirstName($firstName)
                ->setUserLastName($lastName)
                ->setUserEmail($email)
                ->setPassword($hashedPassword)
                ->setUserRole($role);

            return $user;
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
        }
    }

    public function getUserRole(): string
    {
        return $this->userRole;
    }

    public function setUserRole(string $userRole): self
    {
        if (!in_array($userRole, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException('Rôle invalide fourni.');
        }
        
        $this->userRole = $userRole;
        return $this;
    }
}
