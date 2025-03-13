<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Service centralisé pour les opérations de sécurité
 */
class SecurityService
{
    public function __construct(
        private Security $security,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * Hache un mot de passe en clair
     */
    public function hashPassword(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }

    /**
     * Vérifie si un mot de passe en clair correspond au mot de passe haché d'un utilisateur
     */
    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * Récupère l'utilisateur actuellement connecté
     */
    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Récupère l'utilisateur actuellement connecté ou lance une exception
     * 
     * @throws AccessDeniedException Si aucun utilisateur n'est connecté
     */
    public function getCurrentUserOrThrow(): User
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw new AccessDeniedException('Accès refusé. Utilisateur non authentifié.');
        }
        return $user;
    }

    public function isGranted(string $attribute, mixed $subject = null): bool
    {
        return $this->security->isGranted($attribute, $subject);
    }
} 