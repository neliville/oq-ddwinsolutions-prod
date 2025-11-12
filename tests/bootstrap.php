<?php

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use App\Tests\Fixtures\TestDataSeeder;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new Kernel('test', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

/** @var EntityManagerInterface $entityManager */
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

$schemaTool = new SchemaTool($entityManager);
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool->dropDatabase();
if ($metadata !== []) {
    $schemaTool->createSchema($metadata);
}

TestDataSeeder::seed($entityManager);

$kernel->shutdown();
