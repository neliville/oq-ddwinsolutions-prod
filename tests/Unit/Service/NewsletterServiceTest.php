<?php

namespace App\Tests\Unit\Service;

use App\Entity\NewsletterSubscriber;
use App\Service\MailerService;
use App\Service\NewsletterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NewsletterServiceTest extends TestCase
{
    private MailerService&MockObject $mailerService;
    private NewsletterService $newsletterService;

    protected function setUp(): void
    {
        $this->mailerService = $this->createMock(MailerService::class);
        $this->newsletterService = new NewsletterService($this->mailerService);
    }

    public function testSendWelcomeEmailDelegatesToMailer(): void
    {
        $subscriber = (new NewsletterSubscriber())->setEmail('unit-test@example.com');

        $this->mailerService
            ->expects($this->once())
            ->method('sendNewsletterWelcomeEmail')
            ->with($subscriber);

        $this->newsletterService->sendWelcomeEmail($subscriber);
    }
}


