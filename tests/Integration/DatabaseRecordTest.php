<?php

namespace App\Tests\Integration;

use App\Entity\Record;
use App\Entity\User;
use App\Repository\RecordRepository;
use App\Repository\UserRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class DatabaseRecordTest extends WebTestCaseWithDatabase
{
    private UserRepository $userRepository;
    private RecordRepository $recordRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->recordRepository = static::getContainer()->get(RecordRepository::class);
    }

    public function testCreateAndRetrieveRecord(): void
    {
        // Créer un utilisateur
        $user = $this->createTestUser();
        
        // Créer un enregistrement
        $record = new Record();
        $record->setTitle('Test Record');
        $record->setType('ishikawa');
        $record->setContent(json_encode(['categories' => []]));
        $record->setUser($user);
        
        $this->entityManager->persist($record);
        $this->entityManager->flush();
        
        // Récupérer l'enregistrement
        $retrievedRecord = $this->recordRepository->find($record->getId());
        
        $this->assertNotNull($retrievedRecord);
        $this->assertEquals('Test Record', $retrievedRecord->getTitle());
        $this->assertEquals('ishikawa', $retrievedRecord->getType());
        $this->assertEquals($user->getId(), $retrievedRecord->getUser()->getId());
    }

    public function testUserCanOnlySeeOwnRecords(): void
    {
        // Créer deux utilisateurs
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');
        
        // Créer des enregistrements pour chaque utilisateur
        $record1 = new Record();
        $record1->setTitle('Record User 1');
        $record1->setType('ishikawa');
        $record1->setContent(json_encode([]));
        $record1->setUser($user1);
        
        $record2 = new Record();
        $record2->setTitle('Record User 2');
        $record2->setType('fivewhy');
        $record2->setContent(json_encode([]));
        $record2->setUser($user2);
        
        $this->entityManager->persist($record1);
        $this->entityManager->persist($record2);
        $this->entityManager->flush();
        
        // Vérifier que chaque utilisateur ne voit que ses propres enregistrements
        $user1Records = $this->recordRepository->findBy(['user' => $user1]);
        $user2Records = $this->recordRepository->findBy(['user' => $user2]);
        
        $this->assertCount(1, $user1Records);
        $this->assertEquals('Record User 1', $user1Records[0]->getTitle());
        
        $this->assertCount(1, $user2Records);
        $this->assertEquals('Record User 2', $user2Records[0]->getTitle());
    }

    public function testRecordCanBeUpdated(): void
    {
        $user = $this->createTestUser();
        
        $record = new Record();
        $record->setTitle('Original Title');
        $record->setType('ishikawa');
        $record->setContent(json_encode([]));
        $record->setUser($user);
        
        $this->entityManager->persist($record);
        $this->entityManager->flush();
        $recordId = $record->getId();
        
        // Mettre à jour
        $record->setTitle('Updated Title');
        $this->entityManager->flush();
        
        // Vérifier
        $updatedRecord = $this->recordRepository->find($recordId);
        $this->assertEquals('Updated Title', $updatedRecord->getTitle());
    }

    public function testRecordCanBeDeleted(): void
    {
        $user = $this->createTestUser();
        
        $record = new Record();
        $record->setTitle('To Delete');
        $record->setType('ishikawa');
        $record->setContent(json_encode([]));
        $record->setUser($user);
        
        $this->entityManager->persist($record);
        $this->entityManager->flush();
        $recordId = $record->getId();
        
        // Supprimer
        $this->entityManager->remove($record);
        $this->entityManager->flush();
        
        // Vérifier
        $deletedRecord = $this->recordRepository->find($recordId);
        $this->assertNull($deletedRecord);
    }
}

