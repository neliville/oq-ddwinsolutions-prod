<?php

namespace App\Tests\Functional\Admin;

use App\Entity\ContactMessage;
use App\Tests\Functional\FixturesTrait;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class MessagesControllerTest extends WebTestCaseWithDatabase
{
    use FixturesTrait;

    public function testMessagesListIsAccessible(): void
    {
        $entityManager = $this->entityManager;
        $entityManager->createQuery('DELETE FROM App\\Entity\\ContactMessage m')->execute();
        $entityManager->createQuery('DELETE FROM App\\Entity\\User u')->execute();
        $this->loadAdminUser($entityManager);

        $message = new ContactMessage();
        $message->setName('Jean Dupont');
        $message->setEmail('jean.dupont@example.com');
        $message->setSubject('Demande d\'information');
        $message->setMessage('Bonjour, j\'aimerais en savoir plus sur vos outils.');
        $entityManager->persist($message);
        $entityManager->flush();

        $this->client->loginUser($this->getAdminUser($entityManager));
        $this->client->request('GET', '/admin/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Messages de contact');
    }
}
