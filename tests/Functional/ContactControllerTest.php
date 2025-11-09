<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactControllerTest extends WebTestCase
{
    public function testContactPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testContactFormSubmissionWithValidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact/');

        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-contact-' . uniqid() . '@example.com';
        
        $form = $crawler->selectButton('Envoyer')->form([
            'contact_form[name]' => 'Test User',
            'contact_form[email]' => $uniqueEmail,
            'contact_form[subject]' => 'support',
            'contact_form[message]' => 'Ceci est un message de test',
        ]);

        $client->submit($form);

        // Vérifier l'envoi de l'email d'accusé de réception (avant de suivre la redirection)
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage(0);
        $this->assertEmailAddressContains($email, 'To', $uniqueEmail);
        $this->assertEmailHtmlBodyContains($email, 'Test User');
        $this->assertEmailHtmlBodyContains($email, 'support');
        $this->assertEmailHtmlBodyContains($email, 'Ceci est un message de test');

        // Devrait rediriger après soumission réussie
        $this->assertResponseRedirects('/contact/');
        $client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }

    public function testContactFormValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact/');

        // Soumettre le formulaire avec des données invalides
        $form = $crawler->selectButton('Envoyer')->form([
            'contact_form[name]' => '', // Vide - devrait échouer NotBlank
            'contact_form[email]' => 'invalid-email', // Format invalide - devrait échouer Email constraint
            'contact_form[subject]' => '', // Vide - devrait échouer NotBlank
            'contact_form[message]' => '', // Vide - devrait échouer NotBlank
        ]);

        $crawler = $client->submit($form);

        // Quand le formulaire est soumis mais invalide, Symfony reste sur la page (HTTP 200)
        // et affiche le formulaire avec les erreurs de validation
        // Vérifier que la réponse n'est pas une redirection (ce qui indiquerait un succès)
        $this->assertFalse(
            $client->getResponse()->isRedirect(),
            'Le formulaire invalide ne devrait pas rediriger'
        );
        
        // Vérifier que le formulaire est toujours présent
        $this->assertSelectorExists('form[name="contact_form"]');
        
        // Vérifier que la page contient du texte indiquant des erreurs de validation
        // Symfony affiche généralement les erreurs via form_row() qui inclut les messages
        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString(
            'Veuillez',
            $responseContent,
            'Le formulaire devrait contenir des messages d\'erreur de validation'
        );
    }
}
