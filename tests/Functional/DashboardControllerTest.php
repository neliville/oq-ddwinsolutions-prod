<?php

namespace App\Tests\Functional;

use App\Entity\AmdecAnalysis;
use App\Entity\EightDAnalysis;
use App\Entity\FiveWhyAnalysis;
use App\Entity\IshikawaAnalysis;
use App\Entity\ParetoAnalysis;
use App\Entity\QqoqccpAnalysis;
use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
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

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord');
        $this->assertSelectorTextContains('body', 'Qu’est-ce qui nécessite mon attention aujourd’hui');
        $this->assertSelectorTextContains('body', 'CAPA en retard');
        $this->assertSelectorExists('[data-feature="ai-placeholder"]#dashboard-ai-suggestions-slot');
        $this->assertGreaterThan(0, $this->client->getCrawler()->filter('[data-slot="button"]')->count(), 'Au moins un bouton toolkit (ex. Nouvelle analyse)');
        $this->assertGreaterThan(5, $this->client->getCrawler()->filter('.rounded-lg.border.bg-card.text-card-foreground')->count(), 'Cartes cockpit / KPI en composant Card');
        $this->assertSelectorTextContains('body', 'Pilotage :');
    }

    public function testDashboardShowsActivationOnboardingWizardForEligibleUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-onb-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-controller="onboarding-wizard"]');
        $this->assertSelectorExists('[data-onboarding-wizard-csrf-value]');
        $this->assertSelectorTextContains('[data-controller="onboarding-wizard"]', 'Commencez par votre première action utile');
        $this->assertSelectorTextNotContains('[data-controller="onboarding-wizard"]', 'Personnalisez votre espace QHSE');
        $this->assertSelectorExists('[data-onboarding-wizard-total-steps-value="5"]');
        $this->assertSelectorExists('[data-onboarding-field="job_function"]');
        $this->assertSelectorExists('[data-onboarding-field="piloting_focus"]');
        $this->assertSelectorExists('[data-onboarding-guided-action]');
    }

    public function testDashboardDoesNotShowActivationWizardForLegacyOnboardedUser(): void
    {
        $user = $this->createDashboardUser('test-dashboard-legacy-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardDoesNotShowActivationWizardForAdmin(): void
    {
        $user = $this->createDashboardUser('test-dashboard-admin-' . uniqid() . '@example.com', ['ROLE_ADMIN']);
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('[data-controller="onboarding-wizard"]');
    }

    public function testDashboardHidesCapaBlockWhenPreferenceDisabled(): void
    {
        $uniqueEmail = 'test-dashboard-capa-' . uniqid() . '@example.com';
        $user = new User();
        $user->setEmail($uniqueEmail);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(true);
        $prefs->setDashboardVisibility(['capa' => false]);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('2. CAPA (vue synthétique)', $this->client->getResponse()->getContent());
    }

    /**
     * @param list<string> $roles
     */
    private function createDashboardUser(string $email, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles($roles);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}

