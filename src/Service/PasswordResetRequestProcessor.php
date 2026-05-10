<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Logique partagée entre {@see \App\Controller\ResetPasswordController} (page publique)
 * et {@see \App\Controller\PreferencesController} (dialogue connecté).
 */
final readonly class PasswordResetRequestProcessor
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private string $appEmail,
        private string $appName,
        private string $appUrl,
    ) {
    }

    public function process(string $email, MailerInterface $mailer): PasswordResetRequestResult
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if (!$user instanceof User) {
            return PasswordResetRequestResult::userNotFound();
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return PasswordResetRequestResult::throttled();
        }

        $emailMessage = (new TemplatedEmail())
            ->from(new Address($this->appEmail, $this->appName))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'app_name' => $this->appName,
                'app_url' => $this->appUrl,
                'app_email' => $this->appEmail,
                'appEmail' => $this->appEmail,
            ]);

        try {
            $mailer->send($emailMessage);
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l’envoi de l’email de réinitialisation du mot de passe.', [
                'exception' => $e,
            ]);
            $this->resetPasswordHelper->removeResetRequest($resetToken->getToken());

            return PasswordResetRequestResult::mailFailed(
                'L’envoi de l’email a échoué. Réessayez dans quelques minutes ou contactez le support à support@outils-qualite.com.'
            );
        }

        return PasswordResetRequestResult::sent($resetToken);
    }
}
