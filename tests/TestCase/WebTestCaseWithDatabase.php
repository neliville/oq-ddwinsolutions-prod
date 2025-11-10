<?php

namespace App\Tests\TestCase;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

abstract class WebTestCaseWithDatabase extends WebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected UserPasswordHasherInterface $passwordHasher;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        
        $this->refreshDatabaseSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        $this->entityManager?->clear();
    }

    protected function createTestUser(string $email = 'test@outils-qualite.com', string $password = 'test123', array $roles = ['ROLE_USER']): User
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setRoles($roles);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    protected function clearDatabase(): void
    {
        // Supprimer toutes les entités de la base de test
        $entities = [
            'Record',
            'User',
            'ContactMessage',
            'NewsletterSubscriber',
            'BlogPost',
            'PageView',
            'ExportLog',
            'CmsPage',
            'AdminLog',
        ];
        
        foreach ($entities as $entityName) {
            $repository = $this->entityManager->getRepository("App\\Entity\\{$entityName}");
            $entities = $repository->findAll();
            
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }
        }
        
        $this->entityManager->flush();
    }

    protected function refreshDatabaseSchema(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropDatabase();
        if ($metadata !== []) {
            $schemaTool->createSchema($metadata);
        }
    }
}

