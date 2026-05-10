<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use Symfony\Component\HttpFoundation\Response;

final class OnboardingControllerTest extends WebTestCaseWithDatabase
{
    public function testOnboardingStepRequiresAuthentication(): void
    {
        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => 'invalid',
            'step' => '1',
            'value' => 'qse_lead',
        ]);
        $this->assertResponseRedirects('/login');
    }

    public function testOnboardingStepRejectsInvalidCsrf(): void
    {
        $user = $this->createUser('onb-csrf-' . uniqid() . '@example.com');
        $this->client->loginUser($user);
        $this->client->request('POST', '/preferences/onboarding/step', [
            '_token' => 'bad-token',
            'step' => '1',
            'value' => 'qse_lead',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOnboardingStepSavesJobFunctionAndCompletesAfterStep6(): void
    {
        $user = $this->createUser('onb-flow-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrfCrawler = $this->client->getCrawler()->filter('[data-onboarding-wizard-csrf-value]');
        self::assertGreaterThan(0, $csrfCrawler->count(), 'Jeton CSRF onboarding présent sur le dashboard.');
        $csrfToken = $csrfCrawler->first()->attr('data-onboarding-wizard-csrf-value');
        self::assertNotEmpty($csrfToken);

        $steps = [
            1 => 'qse_lead',
            2 => 'solo',
            3 => 'industry',
            4 => 'iso_9001',
            5 => 'audit',
            6 => 'search',
        ];

        foreach ($steps as $step => $value) {
            $this->client->request('POST', '/preferences/onboarding/step', [
                '_token' => $csrfToken,
                'step' => (string) $step,
                'value' => $value,
            ]);
            $this->assertResponseIsSuccessful();
            $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertTrue($data['ok']);
            if ($step === 6) {
                $this->assertTrue($data['completed']);
            } else {
                $this->assertFalse($data['completed']);
            }
        }

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
    }

    public function testSkipOnboardingMarksCompleted(): void
    {
        $user = $this->createUser('onb-skip-' . uniqid() . '@example.com');
        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setProfileOnboardingCompleted(false);
        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/dashboard');
        $csrfCrawler = $this->client->getCrawler()->filter('[data-onboarding-wizard-csrf-value]');
        $csrfToken = $csrfCrawler->first()->attr('data-onboarding-wizard-csrf-value');
        self::assertNotEmpty($csrfToken);

        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => $csrfToken,
        ]);
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($data['ok']);
        $this->assertTrue($data['completed']);

        $this->entityManager->clear();
        $prefs = $this->entityManager->getRepository(UserPreferences::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserPreferences::class, $prefs);
        $this->assertTrue($prefs->isProfileOnboardingCompleted());
    }

    public function testSkipOnboardingRejectsInvalidCsrf(): void
    {
        $user = $this->createUser('onb-skip-csrf-' . uniqid() . '@example.com');
        $this->client->loginUser($user);
        $this->client->request('POST', '/preferences/onboarding/skip', [
            '_token' => 'invalid',
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test123456!'));
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
