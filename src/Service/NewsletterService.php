<?php

namespace App\Service;

use App\Entity\NewsletterSubscriber;

class NewsletterService
{
    public function __construct(
        private readonly MailerService $mailerService,
    ) {
    }

    /**
     * Envoie un email de bienvenue à un nouvel abonné à la newsletter
     * Utilise le MailerService pour l'envoi
     */
    public function sendWelcomeEmail(NewsletterSubscriber $subscriber): void
    {
        $this->mailerService->sendNewsletterWelcomeEmail($subscriber);
    }
}

