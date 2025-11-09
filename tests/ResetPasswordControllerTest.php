<?php

namespace App\Tests;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\Mime\MessageConverter;

class ResetPasswordControllerTest extends WebTestCaseWithDatabase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testResetPasswordController(): void
    {
        // Create a test user with a known password
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'AncienMotdepasse123'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Test Request reset password page
        $this->client->request('GET', '/reset-password');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Mot de passe oublié');

        // Submit the reset password form and test email message is queued / sent
        $this->client->submitForm('reset_password_request_submit', [
            'reset_password_request_form[email]' => 'me@example.com',
        ]);

        // Ensure the reset password email was sent
        self::assertEmailCount(1);

        $email = MessageConverter::toEmail($this->getMailerMessage(0));

        self::assertGreaterThan(0, $this->entityManager->getRepository(ResetPasswordRequest::class)->count([]));

        self::assertEmailAddressContains($email, 'from', 'support@outils-qualite.com');
        self::assertEmailAddressContains($email, 'to', 'me@example.com');
        self::assertEmailTextBodyContains($email, 'Ce lien est valide');

        self::assertResponseRedirects('/reset-password/check-email');

        // Test check email landing page shows correct "expires at" time
        $crawler = $this->client->followRedirect();

        self::assertPageTitleContains('Email de réinitialisation envoyé');
        self::assertStringContainsString('Ce lien expire dans', $crawler->html());

        // Extract the reset link from the email HTML body
        $htmlBody = $email->getHtmlBody();
        $previousUseErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($htmlBody);
        libxml_use_internal_errors($previousUseErrors);

        $linkNode = $dom->getElementsByTagName('a')->item(0);
        self::assertNotNull($linkNode, 'Aucun lien de réinitialisation trouvé dans l’email.');

        $resetUrl = $linkNode->getAttribute('href');
        $parsed = parse_url($resetUrl);
        $resetPath = ($parsed['path'] ?? '') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');

        $this->client->request('GET', $resetPath);

        self::assertResponseRedirects('/reset-password/reset');

        while ($this->client->getResponse()->isRedirection()) {
            $this->client->followRedirect();
        }

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Définir un nouveau mot de passe');

        // Test we can set a new password
        $this->client->submitForm('change_password_submit', [
            'change_password_form[plainPassword][first]' => 'newStrongPassword123',
            'change_password_form[plainPassword][second]' => 'newStrongPassword123',
        ]);

        self::assertResponseRedirects('/login');

        $updatedUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'me@example.com']);

        self::assertInstanceOf(User::class, $updatedUser);
        self::assertTrue($this->passwordHasher->isPasswordValid($updatedUser, 'newStrongPassword123'));
    }
}
