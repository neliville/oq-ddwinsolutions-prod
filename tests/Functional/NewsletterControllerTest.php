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

    public function testUnsubscribeFormDisplay(): void
    {
        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail('unsubscribe-test@example.com');
        $subscriber->setActive(true);
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $token = $subscriber->getUnsubscribeToken();
        $this->client->request('GET', '/newsletter/unsubscribe/' . $token);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Vous êtes bien désinscrit(e)');
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[type="checkbox"]');
    }

    public function testUnsubscribeWithReasons(): void
    {
        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail('unsubscribe-reasons@example.com');
        $subscriber->setActive(true);
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $token = $subscriber->getUnsubscribeToken();
        $crawler = $this->client->request('GET', '/newsletter/unsubscribe/' . $token);
        
        $form = $crawler->selectButton('Envoyer mon retour')->form();
        
        // Récupérer le nom du formulaire depuis le DOM (basé sur le DTO)
        $formName = $form->getName();
        
        // Pour les cases à cocher multiples (expanded => true), Symfony génère des champs avec indices numériques
        // Les indices correspondent à l'ordre des choix dans le formulaire (0 = too_many, 1 = not_relevant)
        // On utilise setValue() avec la valeur du choix pour cocher chaque case
        $form[$formName . '[reasons][0]']->setValue('too_many');
        $form[$formName . '[reasons][1]']->setValue('not_relevant');
        $form[$formName . '[comment]'] = 'Le contenu ne m\'intéresse plus.';

        $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Merci pour votre retour');

        // Récupérer l'entité depuis la base de données
        $this->entityManager->clear();
        $subscriber = $this->entityManager->getRepository(NewsletterSubscriber::class)
            ->findOneBy(['email' => 'unsubscribe-reasons@example.com']);
        $this->assertNotNull($subscriber, 'Subscriber should exist in database');
        $this->assertFalse($subscriber->isActive());
        $this->assertNotNull($subscriber->getUnsubscribedAt());
        $reasons = $subscriber->getUnsubscribeReasons();
        $this->assertIsArray($reasons);
        $this->assertNotEmpty($reasons);
        $this->assertContains('too_many', $reasons);
        $this->assertContains('not_relevant', $reasons);
        $this->assertEquals('Le contenu ne m\'intéresse plus.', $subscriber->getUnsubscribeComment());
    }

    public function testUnsubscribeWithoutReasons(): void
    {
        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail('unsubscribe-skip@example.com');
        $subscriber->setActive(true);
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $token = $subscriber->getUnsubscribeToken();
        $crawler = $this->client->request('GET', '/newsletter/unsubscribe/' . $token);
        
        // Soumettre le formulaire sans sélectionner de raisons
        $form = $crawler->selectButton('Passer cette étape')->form();
        $this->client->submit($form);
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Merci pour votre retour');

        // Récupérer l'entité depuis la base de données
        $this->entityManager->clear();
        $subscriber = $this->entityManager->getRepository(NewsletterSubscriber::class)
            ->findOneBy(['email' => 'unsubscribe-skip@example.com']);
        $this->assertNotNull($subscriber);
        $this->assertFalse($subscriber->isActive());
        $this->assertNotNull($subscriber->getUnsubscribedAt());
    }

    public function testUnsubscribeAlreadyUnsubscribed(): void
    {
        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail('already-unsubscribed@example.com');
        $subscriber->setActive(false);
        $subscriber->unsubscribe();
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $token = $subscriber->getUnsubscribeToken();
        $this->client->request('GET', '/newsletter/unsubscribe/' . $token);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Vous êtes déjà désabonné');
    }

    public function testUnsubscribeWithInvalidToken(): void
    {
        $this->client->request('GET', '/newsletter/unsubscribe/invalid-token-12345');

        $this->assertResponseRedirects('/');
        $this->client->followRedirect();
        // Vérifier qu'on est bien redirigé vers la page d'accueil
        $this->assertResponseIsSuccessful();
    }
}
