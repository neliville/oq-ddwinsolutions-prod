<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\UserPreferences;
use App\Repository\UserPreferencesRepository;
use App\Tests\TestCase\WebTestCaseWithDatabase;
use App\UserPreferences\NotificationFrequency;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class UserQhseDigestCommandTest extends WebTestCaseWithDatabase
{
    public function testDigestDryRunListsEligibleUser(): void
    {
        $email = 'digest-eligible-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');

        /** @var UserPreferencesRepository $prefsRepo */
        $prefsRepo = $this->entityManager->getRepository(UserPreferences::class);
        $prefs = $prefsRepo->getOrCreateForUser($user);
        $prefs->setNotifyWeeklyDigest(true);
        $prefs->setNotificationFrequency(NotificationFrequency::WEEKLY);
        $this->entityManager->flush();

        $application = new Application($this->client->getKernel());
        $application->setAutoExit(false);

        $tester = new CommandTester($application->find('app:user:qhse-digest'));
        $tester->execute(['--dry-run' => true]);

        self::assertStringContainsString('Digest QHSE', $tester->getDisplay());
        self::assertStringContainsString($email, $tester->getDisplay());
    }
}
