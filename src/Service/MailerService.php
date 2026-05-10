<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\Lead;
use App\Entity\NewsletterSubscriber;
use App\Entity\Qse\Audit;
use App\Entity\Qse\CAPAAction;
use App\Entity\SharedAccess;
use App\Entity\User;
use App\Entity\UserInvitation;
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
                'app_email' => $this->appEmail,
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
                'app_email' => $this->appEmail,
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
     * Envoie un email de confirmation après utilisation d'un outil (capture lead).
     */
    public function sendToolConfirmationEmail(Lead $lead): void
    {
        $recipientEmail = $lead->getEmail();
        if (null === $recipientEmail || '' === trim($recipientEmail)) {
            return;
        }

        $toolLabel = $lead->getTool() ?: 'outil';
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($recipientEmail, $lead->getName() ?: null))
            ->subject('Merci d\'avoir utilisé notre ' . $toolLabel . ' – ' . $this->appName)
            ->htmlTemplate('emails/lead/confirmation.html.twig')
            ->textTemplate('emails/lead/confirmation.txt.twig')
            ->context([
                'lead' => $lead,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'app_email' => $this->appEmail,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Notifie l'admin d'un lead qualifié (score > 50).
     */
    public function sendAdminLeadNotification(Lead $lead): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($this->appEmail, $this->appName))
            ->subject('[Lead qualifié] ' . ($lead->getEmail() ?? 'Sans email') . ' – ' . ($lead->getTool() ?? 'N/A'))
            ->htmlTemplate('emails/lead/admin_notification.html.twig')
            ->textTemplate('emails/lead/admin_notification.txt.twig')
            ->context([
                'lead' => $lead,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Synthèse QHSE hebdomadaire par email.
     *
     * V2 : agréger retards CAPA, audits à préparer, etc. et utiliser des templates Twig dédiés.
     * Ne pas appeler en production tant que le contenu n’est pas validé : corps volontairement absent.
     */
    public function sendWeeklyQhseDigest(User $user): void
    {
        // Stub documenté — pas d’envoi pour éviter des e-mails vides ou incomplets (V2 : TemplatedEmail + contenu métier).
        if ('' === (string) $user->getEmail()) {
            return;
        }
    }

    public function sendCollaboratorInvitationEmail(UserInvitation $invitation, string $acceptUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($invitation->getEmail()))
            ->subject($this->appName.' — Invitation à collaborer sur votre pilotage QHSE')
            ->htmlTemplate('emails/collaboration/invitation.html.twig')
            ->textTemplate('emails/collaboration/invitation.txt.twig')
            ->context([
                'invitation' => $invitation,
                'accept_url' => $acceptUrl,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendSharedAuditAccessEmail(SharedAccess $share, ?Audit $audit, string $shareUrl): void
    {
        $to = $share->getInvitedEmail();
        if ($to === null || $to === '') {
            return;
        }
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($to))
            ->subject($this->appName.' — Accès lecture à un audit partagé')
            ->htmlTemplate('emails/collaboration/shared_audit.html.twig')
            ->textTemplate('emails/collaboration/shared_audit.txt.twig')
            ->context([
                'share' => $share,
                'audit' => $audit,
                'share_url' => $shareUrl,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendSharedCapaAccessEmail(SharedAccess $share, ?CAPAAction $capa, string $shareUrl): void
    {
        $to = $share->getInvitedEmail();
        if ($to === null || $to === '') {
            return;
        }
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($to))
            ->subject($this->appName.' — Accès lecture à une CAPA partagée')
            ->htmlTemplate('emails/collaboration/shared_capa.html.twig')
            ->textTemplate('emails/collaboration/shared_capa.txt.twig')
            ->context([
                'share' => $share,
                'capa' => $capa,
                'share_url' => $shareUrl,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendInvitationAcceptedToOwnerEmail(UserInvitation $invitation): void
    {
        $owner = $invitation->getOwner();
        if ($owner === null || $owner->getEmail() === null || $owner->getEmail() === '') {
            return;
        }
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address((string) $owner->getEmail()))
            ->subject($this->appName.' — Votre invitation a été acceptée')
            ->htmlTemplate('emails/collaboration/invitation_accepted_owner.html.twig')
            ->textTemplate('emails/collaboration/invitation_accepted_owner.txt.twig')
            ->context([
                'invitation' => $invitation,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'dashboard_url' => $this->appUrl.'/dashboard',
            ]);

        $this->mailer->send($email);
    }

    public function sendInvitationExpiredEmail(UserInvitation $invitation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to(new Address($invitation->getEmail()))
            ->subject($this->appName.' — Invitation expirée')
            ->htmlTemplate('emails/collaboration/invitation_expired_invitee.html.twig')
            ->textTemplate('emails/collaboration/invitation_expired_invitee.txt.twig')
            ->context([
                'invitation' => $invitation,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
            ]);

        $this->mailer->send($email);
    }
}

