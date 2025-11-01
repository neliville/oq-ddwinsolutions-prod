<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // Rediriger selon le rôle
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // Admin → Dashboard admin
            $response = new RedirectResponse(
                $this->urlGenerator->generate('app_admin_dashboard_index')
            );
        } else {
            // Utilisateur standard → Dashboard utilisateur
            $response = new RedirectResponse(
                $this->urlGenerator->generate('app_dashboard_index')
            );
        }
        
        $event->setResponse($response);
    }
}

