<?php

namespace App\Tests\Unit\Service;

use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Entity\User;
use App\Service\MailerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class MailerServiceTest extends TestCase
{
    private MailerService $mailerService;
    private MailerInterface&MockObject $mailer;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mailerService = new MailerService(
            $this->mailer,
            'Test App',
            'test@example.com',
            'https://test.example.com'
        );
    }

    public function testSendWelcomeEmail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) use ($user) {
                return $email instanceof \Symfony\Bridge\Twig\Mime\TemplatedEmail
                    && $email->getTo()[0]->getAddress() === $user->getEmail()
                    && $email->getSubject() === 'Bienvenue sur Test App !';
            }));

        $this->mailerService->sendWelcomeEmail($user);
    }

    public function testSendNewsletterWelcomeEmail(): void
    {
        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail('subscriber@example.com');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) use ($subscriber) {
                return $email instanceof \Symfony\Bridge\Twig\Mime\TemplatedEmail
                    && $email->getTo()[0]->getAddress() === $subscriber->getEmail()
                    && $email->getSubject() === 'Merci pour votre abonnement à notre newsletter !';
            }));

        $this->mailerService->sendNewsletterWelcomeEmail($subscriber);
    }

    public function testSendContactAcknowledgement(): void
    {
        $contact = new ContactMessage();
        $contact
            ->setName('Jane Doe')
            ->setEmail('jane.doe@example.com')
            ->setSubject('Support')
            ->setMessage("Bonjour, j'ai besoin d'aide.");

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) use ($contact) {
                return $email instanceof \Symfony\Bridge\Twig\Mime\TemplatedEmail
                    && $email->getTo()[0]->getAddress() === $contact->getEmail()
                    && str_contains($email->getSubject(), $contact->getSubject())
                    && ($email->getContext()['contact'] ?? null) === $contact;
            }));

        $this->mailerService->sendContactAcknowledgement($contact);
    }
}

