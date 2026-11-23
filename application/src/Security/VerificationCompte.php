<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VerificationCompte implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $utilisateur): void
    {
        if ($utilisateur instanceof Utilisateur && !$utilisateur->isActif()) {
            throw new CustomUserMessageAccountStatusException("Ce compte est désactivé. Contactez l'administrateur.");
        }
    }

    public function checkPostAuth(UserInterface $utilisateur): void
    {
    }
}
