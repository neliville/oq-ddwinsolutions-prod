<?php

namespace App\Tests\TestCase;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Trait pour gérer la création du schéma de base de données dans les tests
 * Selon la documentation Symfony: https://symfony.com/doc/current/testing.html
 */
trait DatabaseTestCase
{
    /**
     * Crée ou met à jour le schéma de base de données
     * Utilise updateSchema qui gère automatiquement la création ou la mise à jour
     */
    protected function createDatabaseSchema(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        
        // Utiliser updateSchema au lieu de createSchema pour éviter les erreurs si le schéma existe
        // updateSchema crée les tables si elles n'existent pas, ou les met à jour si elles existent
        $schemaTool->updateSchema($metadata, true);
    }

    /**
     * Supprime et recrée le schéma de base de données
     * Utile quand on veut un schéma complètement propre
     */
    protected function recreateDatabaseSchema(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        
        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Exception $e) {
            // Ignorer si le schéma n'existe pas encore
        }
        
        $schemaTool->createSchema($metadata);
    }
}
