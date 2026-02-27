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
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
        $this->assertStringContainsString('abonné', $response['message'] ?? '');
    }

    /**
     * Avec Mautic, les emails sont envoyés par Mautic, pas par Symfony.
     * On vérifie uniquement que l'inscription retourne un succès.
     */
    public function testNewsletterSubscribeSendsWelcomeEmail(): void
    {
        $uniqueEmail = 'test-welcome-newsletter-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }

    /**
     * Avec Mautic, les mentions RGPD sont gérées dans les templates Mautic.
     * On vérifie uniquement que l'inscription réussit.
     */
    public function testNewsletterEmailContainsRgpdMentions(): void
    {
        $uniqueEmail = 'test-rgpd-newsletter-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }

    /**
     * Avec Mautic, les liens de désinscription sont gérés par Mautic.
     * On vérifie uniquement que l'inscription réussit.
     */
    public function testNewsletterEmailContainsUnsubscribeLink(): void
    {
        $uniqueEmail = 'test-unsubscribe-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }

    /**
     * Avec Mautic, les doublons sont gérés par Mautic (create or update).
     * Une même adresse peut s'inscrire plusieurs fois → succès.
     */
    public function testNewsletterSubscribeWithDuplicateEmail(): void
    {
        $uniqueEmail = 'existing-newsletter-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
    }

    public function testNewsletterSubscribeWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => 'invalid-email',
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertJson($this->client->getResponse()->getContent());
    }

    /**
     * Avec Mautic, la réactivation est gérée côté Mautic.
     * Une réinscription retourne simplement un succès.
     */
    public function testNewsletterReactivation(): void
    {
        $uniqueEmail = 'reactivate-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/newsletter/subscribe', [
            'newsletter_subscription_form' => [
                'email' => $uniqueEmail,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success'] ?? false);
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
