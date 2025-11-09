<?php

namespace App\Tests\Functional;

use App\Entity\AmdecAnalysis;
use App\Entity\EightDAnalysis;
use App\Entity\FiveWhyAnalysis;
use App\Entity\IshikawaAnalysis;
use App\Entity\ParetoAnalysis;
use App\Entity\QqoqccpAnalysis;
use App\Entity\User;
use App\Tests\TestCase\WebTestCaseWithDatabase;

class DashboardControllerTest extends WebTestCaseWithDatabase
{
    public function testDashboardRequiresAuthentication(): void
    {
        $this->client->request('GET', '/dashboard');

        // Devrait rediriger vers la page de connexion
        $this->assertResponseRedirects('/login');
    }

    public function testDashboardWithAuthenticatedUser(): void
    {
        // Créer un utilisateur avec un email unique
        $uniqueEmail = 'test-dashboard-' . uniqid() . '@example.com';
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Créer quelques analyses de test avec les nouvelles entités
        for ($i = 1; $i <= 3; $i++) {
            $analysis = new IshikawaAnalysis();
            $analysis->setTitle("Diagramme Ishikawa {$i}");
            $analysis->setProblem("Problème {$i}");
            $analysis->setData(json_encode([
                'categories' => [
                    ['name' => 'Méthode', 'causes' => []],
                    ['name' => 'Matériel', 'causes' => []],
                ],
            ]));
            $analysis->setUser($user);
            $this->entityManager->persist($analysis);
        }

        for ($i = 1; $i <= 2; $i++) {
            $analysis = new FiveWhyAnalysis();
            $analysis->setTitle("Analyse 5 Pourquoi {$i}");
            $analysis->setProblem('Problème test');
            $analysis->setData(json_encode([
                'problem' => 'Problème test',
                'questions' => [],
            ]));
            $analysis->setUser($user);
            $this->entityManager->persist($analysis);
        }

        $qqoqccp = new QqoqccpAnalysis();
        $qqoqccp->setTitle('Analyse QQOQCCP');
        $qqoqccp->setSubject('Sujet test');
        $qqoqccp->setData(json_encode(['qui' => 'Equipe A']));
        $qqoqccp->setUser($user);
        $this->entityManager->persist($qqoqccp);

        $amdec = new AmdecAnalysis();
        $amdec->setTitle('Analyse AMDEC');
        $amdec->setSubject('Processus critique');
        $amdec->setData(json_encode(['entries' => []]));
        $amdec->setUser($user);
        $this->entityManager->persist($amdec);

        $pareto = new ParetoAnalysis();
        $pareto->setTitle('Analyse Pareto');
        $pareto->setDescription('Répartition causes');
        $pareto->setData(json_encode(['entries' => []]));
        $pareto->setUser($user);
        $this->entityManager->persist($pareto);

        $eightD = new EightDAnalysis();
        $eightD->setTitle('Rapport 8D');
        $eightD->setDescription('Résolution test');
        $eightD->setData(json_encode(['disciplines' => []]));
        $eightD->setUser($user);
        $this->entityManager->persist($eightD);

        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord');
    }
}

