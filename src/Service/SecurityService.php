<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityService
{
    public function __construct(
        private Security $security,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function hashPassword(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }

    public function isPasswordValid(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    public function getCurrentUserOrThrow(): User
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw new AccessDeniedException('Access Denied. User not authenticated.');
        }
        return $user;
    }

    public function isGranted(string $attribute, mixed $subject = null): bool
    {
        return $this->security->isGranted($attribute, $subject);
    }
} 