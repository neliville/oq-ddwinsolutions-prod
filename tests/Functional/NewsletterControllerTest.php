<?php

namespace App\Tests\Functional;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class NewsletterControllerTest extends WebTestCase
{
    public function testNewsletterSubscribeWithValidEmail(): void
    {
        $client = static::createClient();
        
        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-newsletter-' . uniqid() . '@example.com';
        
        // Envoyer une requête POST avec les données du formulaire Symfony
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier que la réponse est un JSON (car c'est une API)
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        
        // Vérifier que l'abonné a été créé
        $subscriberRepository = static::getContainer()->get(NewsletterSubscriberRepository::class);
        $subscriber = $subscriberRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($subscriber);
        $this->assertTrue($subscriber->isActive());
    }

    public function testNewsletterSubscribeSendsWelcomeEmail(): void
    {
        $client = static::createClient();
        
        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-welcome-newsletter-' . uniqid() . '@example.com';
        
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier qu'un email a été envoyé
        $this->assertEmailCount(1);

        // Récupérer le premier email envoyé
        $email = $this->getMailerMessage(0);

        // Vérifier le destinataire
        $this->assertEmailHeaderSame($email, 'To', $uniqueEmail);

        // Vérifier le sujet
        $this->assertEmailHeaderSame($email, 'Subject', 'Merci pour votre abonnement à notre newsletter !');

        // Vérifier le contenu du corps de l'email
        $this->assertEmailHtmlBodyContains($email, 'Merci');
        $this->assertEmailHtmlBodyContains($email, 'abonné');
        $this->assertEmailHtmlBodyContains($email, 'newsletter');
    }

    public function testNewsletterEmailContainsRgpdMentions(): void
    {
        $client = static::createClient();
        
        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-rgpd-newsletter-' . uniqid() . '@example.com';
        
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier qu'un email a été envoyé
        $this->assertEmailCount(1);
        
        // Récupérer l'email envoyé
        $email = $this->getMailerMessage(0);
        
        if (!$email) {
            $this->fail('Aucun email n\'a été envoyé');
        }

        // Vérifier que l'email contient les mentions RGPD
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
        $client = static::createClient();
        
        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-unsubscribe-' . uniqid() . '@example.com';
        
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier qu'un email a été envoyé
        $this->assertEmailCount(1);
        
        // Récupérer l'email et l'abonné
        $email = $this->getMailerMessage(0);
        
        if (!$email) {
            $this->fail('Aucun email n\'a été envoyé');
        }
        
        $subscriberRepository = static::getContainer()->get(NewsletterSubscriberRepository::class);
        $subscriber = $subscriberRepository->findOneBy(['email' => $uniqueEmail]);
        
        $this->assertNotNull($subscriber);

        // Vérifier que le lien de désabonnement est présent
        $unsubscribeUrl = '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken();
        $this->assertEmailHtmlBodyContains($email, $unsubscribeUrl);
        $this->assertEmailTextBodyContains($email, $unsubscribeUrl);
    }

    public function testNewsletterSubscribeWithDuplicateEmail(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un abonné existant avec un email unique
        $uniqueEmail = 'existing-newsletter-' . uniqid() . '@example.com';
        $existingSubscriber = new NewsletterSubscriber();
        $existingSubscriber->setEmail($uniqueEmail);
        $entityManager->persist($existingSubscriber);
        $entityManager->flush();

        // Tenter de s'abonner avec le même email
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier que la réponse est un JSON avec erreur
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($response['success'] ?? true);
    }

    public function testNewsletterSubscribeWithInvalidEmail(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => 'invalid-email',
            ],
        ]);

        // Vérifier que la réponse est un JSON avec erreur (validation échoue)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testNewsletterReactivation(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un abonné désabonné avec un email unique
        $uniqueEmail = 'reactivate-' . uniqid() . '@example.com';
        $unsubscribed = new NewsletterSubscriber();
        $unsubscribed->setEmail($uniqueEmail);
        $unsubscribed->setActive(false);
        $unsubscribed->setUnsubscribedAt(new \DateTimeImmutable());
        $entityManager->persist($unsubscribed);
        $entityManager->flush();

        // Tenter de s'abonner à nouveau
        $client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        // Vérifier que la réponse est un JSON de succès
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);

        // Vérifier que l'abonnement a été réactivé
        $entityManager->clear();
        $subscriberRepository = $container->get(NewsletterSubscriberRepository::class);
        $reactivated = $subscriberRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertTrue($reactivated->isActive());
        $this->assertNull($reactivated->getUnsubscribedAt());

        // Vérifier qu'un email de bienvenue a été envoyé
        $this->assertEmailCount(1);
    }
}
