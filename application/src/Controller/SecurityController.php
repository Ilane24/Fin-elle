<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'connexion')]
    public function connexion(AuthenticationUtils $authentification): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('accueil');
        }

        $erreur = $authentification->getLastAuthenticationError();
        $message = match (true) {
            $erreur === null => null,
            $erreur instanceof CustomUserMessageAccountStatusException => $erreur->getMessageKey(),
            $erreur instanceof InvalidCsrfTokenException => 'Le formulaire a expiré. Recommencez.',
            default => 'E-mail ou mot de passe incorrect.',
        };

        return $this->render('securite/connexion.html.twig', [
            'dernierEmail' => $authentification->getLastUsername(),
            'erreur' => $message,
        ]);
    }

    #[Route('/deconnexion', name: 'deconnexion')]
    public function deconnexion(): never
    {
        throw new \LogicException('Cette méthode est interceptée par le pare-feu (logout dans security.yaml).');
    }
}
