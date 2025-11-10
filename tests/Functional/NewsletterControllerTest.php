<?php

namespace App\Tests\Functional;

use App\Entity\NewsletterSubscriber;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

class NewsletterControllerTest extends WebTestCaseWithDatabase
{
    public function testNewsletterSubscribeWithValidEmail(): void
    {
        $uniqueEmail = 'test-newsletter-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        
        $subscriberRepository = $this->entityManager->getRepository(NewsletterSubscriber::class);
        $subscriber = $subscriberRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($subscriber);
        $this->assertTrue($subscriber->isActive());
    }

    public function testNewsletterSubscribeSendsWelcomeEmail(): void
    {
        $uniqueEmail = 'test-welcome-newsletter-' . uniqid() . '@example.com';
        
        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertEmailCount(1);

        $email = $this->getMailerMessage(0);

        $this->assertEmailHeaderSame($email, 'To', $uniqueEmail);
        $this->assertEmailHeaderSame($email, 'Subject', 'Merci pour votre abonnement à notre newsletter !');
        $this->assertEmailHtmlBodyContains($email, 'Merci');
        $this->assertEmailHtmlBodyContains($email, 'abonné');
        $this->assertEmailHtmlBodyContains($email, 'newsletter');
    }

    public function testNewsletterEmailContainsRgpdMentions(): void
    {
        $uniqueEmail = 'test-rgpd-newsletter-' . uniqid() . '@example.com';
        
        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertEmailCount(1);
        
        $email = $this->getMailerMessage(0);
        
        if (!$email) {
            $this->fail('Aucun email n\'a été envoyé');
        }

        $this->assertEmailHtmlBodyContains($email, 'Conformité RGPD');
        $this->assertEmailHtmlBodyContains($email, 'droit d\'accès');
        $this->assertEmailHtmlBodyContains($email, 'rectification');
        $this->assertEmailHtmlBodyContains($email, 'suppression');
        $this->assertEmailHtmlBodyContains($email, 'politique de confidentialité');
        $this->assertEmailHtmlBodyContains($email, 'contact@outils-qualite.com');
        $this->assertEmailHtmlBodyContains($email, 'Se désabonner');
    }

    public function testNewsletterEmailContainsUnsubscribeLink(): void
    {
        $uniqueEmail = 'test-unsubscribe-' . uniqid() . '@example.com';
        
        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertEmailCount(1);
        
        $email = $this->getMailerMessage(0);
        
        if (!$email) {
            $this->fail('Aucun email n\'a été envoyé');
        }
        
        $subscriber = $this->entityManager->getRepository(NewsletterSubscriber::class)
            ->findOneBy(['email' => $uniqueEmail]);
         
        $this->assertNotNull($subscriber);

        $unsubscribeUrl = '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken();
        $this->assertEmailHtmlBodyContains($email, $unsubscribeUrl);
        $this->assertEmailTextBodyContains($email, $unsubscribeUrl);
    }

    public function testNewsletterSubscribeWithDuplicateEmail(): void
    {
        $uniqueEmail = 'existing-newsletter-' . uniqid() . '@example.com';
        $existingSubscriber = new NewsletterSubscriber();
        $existingSubscriber->setEmail($uniqueEmail);
        $this->entityManager->persist($existingSubscriber);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson($this->client->getResponse()->getContent());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['success'] ?? true);
    }

    public function testNewsletterSubscribeWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => 'invalid-email',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    public function testNewsletterReactivation(): void
    {
        $uniqueEmail = 'reactivate-' . uniqid() . '@example.com';
        $unsubscribed = new NewsletterSubscriber();
        $unsubscribed->setEmail($uniqueEmail);
        $unsubscribed->setActive(false);
        $unsubscribed->setUnsubscribedAt(new \DateTimeImmutable());
        $this->entityManager->persist($unsubscribed);
        $this->entityManager->flush();

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);

        $this->entityManager->clear();
        $subscriberRepository = $this->entityManager->getRepository(NewsletterSubscriber::class);
        $reactivated = $subscriberRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertTrue($reactivated->isActive());
        $this->assertNull($reactivated->getUnsubscribedAt());

        $this->assertEmailCount(1);
    }
}
