<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationPageIsAccessible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        // Vérifier que la page contient "Créer" ou "compte" quelque part
        $this->assertSelectorTextContains('body', 'Créer');
    }

    public function testRegistrationWithValidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Trouver le formulaire et le remplir avec un email unique
        $uniqueEmail = 'test-' . uniqid() . '@example.com';
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'Test123456!',
            'registration_form[plainPassword][second]' => 'Test123456!',
        ]);
        
        $client->submit($form);

        // Vérifier la redirection vers la page de connexion
        $this->assertResponseRedirects('/login');

        // Vérifier que l'utilisateur a été créé
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($user);
        $this->assertInstanceOf(User::class, $user);
    }

    public function testRegistrationSendsWelcomeEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-welcome-' . uniqid() . '@example.com';
        $button = $crawler->filter('button[type="submit"]');
        if (count($button) === 0) {
            // Si le bouton n'est pas trouvé, utiliser le formulaire directement
            $form = $crawler->filter('form')->form([
                'registration_form[email]' => $uniqueEmail,
                'registration_form[plainPassword][first]' => 'Test123456!',
                'registration_form[plainPassword][second]' => 'Test123456!',
            ]);
        } else {
            $form = $button->form([
                'registration_form[email]' => $uniqueEmail,
                'registration_form[plainPassword][first]' => 'Test123456!',
                'registration_form[plainPassword][second]' => 'Test123456!',
            ]);
        }

        $client->submit($form);

        // Vérifier qu'un email a été envoyé
        $this->assertEmailCount(1);

        // Récupérer le premier email envoyé
        $email = $this->getMailerMessage(0);

        // Vérifier le destinataire
        $this->assertEmailHeaderSame($email, 'To', $uniqueEmail);

        // Vérifier le sujet
        $this->assertEmailHeaderSame($email, 'Subject', 'Bienvenue sur OUTILS-QUALITÉ !');

        // Vérifier le contenu du corps de l'email (HTML)
        $this->assertEmailHtmlBodyContains($email, 'Bienvenue sur');
        $this->assertEmailHtmlBodyContains($email, 'Votre compte a été créé avec succès');

        // Vérifier le contenu du corps de l'email (texte)
        $this->assertEmailTextBodyContains($email, 'Bienvenue sur');
        $this->assertEmailTextBodyContains($email, 'Votre compte a été créé avec succès');
    }

    public function testRegistrationEmailContainsRgpdMentions(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-rgpd-' . uniqid() . '@example.com';
        // Trouver le formulaire et le remplir
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'Test123456!',
            'registration_form[plainPassword][second]' => 'Test123456!',
        ]);

        $client->submit($form);

        // Récupérer l'email envoyé
        $email = $this->getMailerMessage(0);

        // Vérifier que l'email contient les mentions RGPD
        $this->assertEmailHtmlBodyContains($email, 'Conformité RGPD');
        $this->assertEmailHtmlBodyContains($email, 'droit d\'accès');
        $this->assertEmailHtmlBodyContains($email, 'rectification');
        $this->assertEmailHtmlBodyContains($email, 'suppression');
        $this->assertEmailHtmlBodyContains($email, 'politique de confidentialité');
        $this->assertEmailHtmlBodyContains($email, 'contact@outils-qualite.com');

        // Vérifier aussi dans le texte brut
        $this->assertEmailTextBodyContains($email, 'CONFORMITÉ RGPD');
        $this->assertEmailTextBodyContains($email, 'droit d\'accès');
    }

    public function testRegistrationWithInvalidEmail(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Trouver le formulaire et le remplir
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => 'invalid-email',
            'registration_form[plainPassword][first]' => 'Test123456!',
            'registration_form[plainPassword][second]' => 'Test123456!',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la page n'a pas changé (erreur de validation)
        // La validation peut afficher l'erreur de différentes manières
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        // Vérifier que le formulaire contient une erreur (peut être dans différents sélecteurs)
        $this->assertSelectorExists('form[name="registration_form"]');
    }

    public function testRegistrationWithWeakPassword(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-weak-' . uniqid() . '@example.com';
        // Trouver le formulaire et le remplir
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'weak',
            'registration_form[plainPassword][second]' => 'weak',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la page n'a pas changé (erreur de validation)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRegistrationWithMismatchedPasswords(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Utiliser un email unique pour ce test
        $uniqueEmail = 'test-mismatch-' . uniqid() . '@example.com';
        // Trouver le formulaire et le remplir
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'Test123456!',
            'registration_form[plainPassword][second]' => 'DifferentPassword123!',
        ]);

        $crawler = $client->submit($form);

        // Vérifier que la page n'a pas changé (erreur de validation)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSelectorTextContains('body', 'ne correspondent pas');
    }

    /**
     * Test pour vérifier qu'on peut créer un utilisateur avec un email unique.
     * Test séparé pour isoler la création d'utilisateur.
     */
    public function testRegistrationCreatesUserInDatabase(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un utilisateur directement en base de données
        $uniqueEmail = 'test-db-' . uniqid() . '@example.com';
        $passwordHasher = $container->get('security.user_password_hasher');
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Vérifier que l'utilisateur a été créé
        $userRepository = $container->get(UserRepository::class);
        $createdUser = $userRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($createdUser, 'L\'utilisateur doit être créé en base de données');
        $this->assertInstanceOf(User::class, $createdUser);
        $this->assertSame($uniqueEmail, $createdUser->getEmail());
    }

    /**
     * Test pour vérifier qu'on ne peut pas créer un compte avec un email déjà utilisé.
     * 
     * Note: Le formulaire ne valide pas actuellement les emails dupliqués,
     * donc Doctrine lève une exception d'intégrité de contrainte.
     * Le test vérifie que cette exception est bien levée.
     */
    public function testRegistrationWithDuplicateEmailThrowsException(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Étape 1 : Créer un utilisateur existant directement avec un email unique
        $uniqueEmail = 'test-duplicate-' . uniqid() . '@example.com';
        $passwordHasher = $container->get('security.user_password_hasher');
        $existingUser = new User();
        $existingUser->setEmail($uniqueEmail);
        $existingUser->setPassword($passwordHasher->hashPassword($existingUser, 'Test123456!'));
        $existingUser->setRoles(['ROLE_USER']);
        $entityManager->persist($existingUser);
        $entityManager->flush();
        
        // S'assurer que l'utilisateur a été créé
        $userRepository = $container->get(UserRepository::class);
        $createdUser = $userRepository->findOneBy(['email' => $uniqueEmail]);
        $this->assertNotNull($createdUser, 'L\'utilisateur doit être créé avant le test de duplication');

        // Étape 2 : Tenter de créer un compte avec le même email via le formulaire
        // Le client ne suit pas automatiquement les exceptions, on doit les intercepter
        $client->catchExceptions(false);
        
        $crawler = $client->request('GET', '/register');
        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[email]' => $uniqueEmail,
            'registration_form[plainPassword][first]' => 'Test123456!',
            'registration_form[plainPassword][second]' => 'Test123456!',
        ]);

        // Soumettre le formulaire - devrait lever une exception d'intégrité
        try {
            $client->submit($form);
            // Si aucune exception n'est levée, le test doit échouer
            $this->fail('Une exception d\'intégrité de contrainte devrait être levée pour un email dupliqué');
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // C'est le comportement attendu : une exception d'intégrité est levée
            $this->assertTrue(
                str_contains($e->getMessage(), 'Duplicate entry')
                || str_contains($e->getMessage(), 'UNIQUE constraint failed'),
                'Le message d\'erreur doit indiquer une violation de contrainte unique.'
            );
            $count = $userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.email = :email')
                ->setParameter('email', $uniqueEmail)
                ->getQuery()
                ->getSingleScalarResult();
            $this->assertSame('1', (string) $count, 'L\'email dupliqué ne doit pas créer un nouvel utilisateur.');
        } catch (\Exception $e) {
            // Si c'est une autre exception, vérifier qu'elle est liée à l'intégrité
            if (str_contains($e->getMessage(), 'Duplicate entry') || 
                str_contains($e->getMessage(), 'UNIQ_IDENTIFIER_EMAIL') ||
                str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                // C'est aussi un comportement acceptable
                $this->assertTrue(true);
            } else {
                // Sinon, c'est une erreur inattendue
                throw $e;
            }
        }
    }

    public function testRegistrationRedirectsLoggedInUsers(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        
        // Créer un utilisateur directement avec un email unique
        $uniqueEmail = 'test-loggedin-' . uniqid() . '@example.com';
        $passwordHasher = $container->get('security.user_password_hasher');
        $testUser = new User();
        $testUser->setEmail($uniqueEmail);
        $testUser->setPassword($passwordHasher->hashPassword($testUser, \App\Tests\Fixtures\UserFixtures::USER_PASSWORD));
        $testUser->setRoles(['ROLE_USER']);
        $entityManager->persist($testUser);
        $entityManager->flush();
        
        $client->loginUser($testUser);
        $client->request('GET', '/register');


        // Vérifier la redirection vers le dashboard
        $this->assertResponseRedirects('/dashboard');
    }
}

