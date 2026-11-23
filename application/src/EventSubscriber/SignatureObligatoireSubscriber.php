<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignatureObligatoireSubscriber implements EventSubscriberInterface
{
    private const ROUTES_AUTORISEES = ['signature', 'signature_enregistrer', 'connexion', 'deconnexion', 'inscription'];

    public function __construct(private Security $security, private UrlGeneratorInterface $urls)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'verifier'];
    }

    public function verifier(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $utilisateur = $this->security->getUser();
        if (!$utilisateur instanceof Utilisateur || $utilisateur->getSignature() !== null) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route');
        if ($route === '' || str_starts_with($route, '_') || in_array($route, self::ROUTES_AUTORISEES, true)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urls->generate('signature')));
    }
}
