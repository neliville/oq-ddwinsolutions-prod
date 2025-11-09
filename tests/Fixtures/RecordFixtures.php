<?php

namespace App\Tests\Fixtures;

use App\Entity\Record;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour créer des enregistrements de test
 */
class RecordFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('user');

        // Créer quelques enregistrements Ishikawa
        for ($i = 1; $i <= 3; $i++) {
            $record = new Record();
            $record->setTitle("Diagramme Ishikawa {$i}");
            $record->setType('ishikawa');
            $record->setContent(json_encode([
                'categories' => [
                    ['name' => 'Méthode', 'causes' => []],
                    ['name' => 'Matériel', 'causes' => []],
                ],
            ]));
            $record->setUser($user);
            $manager->persist($record);
            $this->addReference("ishikawa_record_{$i}", $record);
        }

        // Créer quelques enregistrements 5 Pourquoi
        for ($i = 1; $i <= 2; $i++) {
            $record = new Record();
            $record->setTitle("Analyse 5 Pourquoi {$i}");
            $record->setType('fivewhy');
            $record->setContent(json_encode([
                'problem' => 'Problème test',
                'questions' => [],
            ]));
            $record->setUser($user);
            $manager->persist($record);
            $this->addReference("fivewhy_record_{$i}", $record);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}

