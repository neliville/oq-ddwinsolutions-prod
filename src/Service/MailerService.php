<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailerService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $appName,
        private readonly string $appEmail,
        private readonly string $appUrl,
    ) {
    }

    /**
     * Envoie un email de bienvenue à un nouvel utilisateur après son inscription
     */
    public function sendWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($user->getEmail()))
            ->subject('Bienvenue sur ' . $this->appName . ' !')
            ->htmlTemplate('emails/user/welcome.html.twig')
            ->textTemplate('emails/user/welcome.txt.twig')
            ->context([
                'user' => $user,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'login_url' => $this->appUrl . '/login',
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email de bienvenue à un nouvel abonné à la newsletter
     */
    public function sendNewsletterWelcomeEmail(NewsletterSubscriber $subscriber): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($subscriber->getEmail()))
            ->subject('Merci pour votre abonnement à notre newsletter !')
            ->htmlTemplate('emails/newsletter/welcome.html.twig')
            ->textTemplate('emails/newsletter/welcome.txt.twig')
            ->context([
                'subscriber' => $subscriber,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'unsubscribe_url' => $this->generateNewsletterUnsubscribeUrl($subscriber),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Envoie un email d'accusé de réception pour un message de contact
     */
    public function sendContactAcknowledgement(ContactMessage $contactMessage): void
    {
        $recipientEmail = $contactMessage->getEmail();

        if (null === $recipientEmail || '' === trim($recipientEmail)) {
            return;
        }

        $subject = 'Nous avons bien reçu votre message';
        if ($contactMessage->getSubject()) {
            $subject .= ' : ' . $contactMessage->getSubject();
        }

        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($recipientEmail, $contactMessage->getName() ?: null))
            ->subject($subject)
            ->htmlTemplate('emails/contact/acknowledgement.html.twig')
            ->textTemplate('emails/contact/acknowledgement.txt.twig')
            ->context([
                'contact' => $contactMessage,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'app_email' => $this->appEmail,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Génère l'URL de désabonnement pour la newsletter
     */
    private function generateNewsletterUnsubscribeUrl(NewsletterSubscriber $subscriber): string
    {
        return $this->appUrl . '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken();
    }
}

