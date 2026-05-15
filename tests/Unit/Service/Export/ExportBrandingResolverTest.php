<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Export;

use App\Entity\User;
use App\Entity\UserPreferences;
use App\Export\Dto\ExportSystemBranding;
use App\Repository\UserPreferencesRepository;
use App\Service\Export\ExportBrandingResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ExportBrandingResolverTest extends TestCase
{
    private UserPreferencesRepository&MockObject $repository;

    private ExportBrandingResolver $resolver;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserPreferencesRepository::class);
        $this->resolver = new ExportBrandingResolver($this->repository);
    }

    public function testResolveForNullUserReturnsSystemOnly(): void
    {
        $view = $this->resolver->resolveForUser(null);

        self::assertFalse($view->hasUserCustomization());
        self::assertNull($view->user);
        self::assertNull($view->profileDisplayName);
        $api = $view->toApiArray();
        self::assertSame(ExportSystemBranding::BRAND_NAME, $api['system']['brandName']);
        self::assertNull($api['exportDisplayName']);
        self::assertArrayNotHasKey('user', $api);
    }

    public function testResolveTrimsAndIgnoresEmptyPreferences(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $prefs = new UserPreferences();
        $prefs->setUser($user);
        $prefs->setExportDisplayName('  ');
        $prefs->setExportJobTitle('Qualification');
        $prefs->setExportCompanyName('DDWIN SOLUTIONS');

        $this->repository
            ->expects(self::once())
            ->method('getOrCreateForUser')
            ->with($user)
            ->willReturn($prefs);

        $view = $this->resolver->resolveForUser($user);

        self::assertTrue($view->hasUserCustomization());
        self::assertNotNull($view->user);
        self::assertNull($view->user->displayName);
        self::assertSame('Qualification', $view->user->jobTitle);
        self::assertSame('DDWIN SOLUTIONS', $view->user->companyName);
        self::assertSame(['Qualification', 'DDWIN SOLUTIONS'], $view->user->headerLines());

        $api = $view->toApiArray();
        self::assertNull($api['exportDisplayName']);
        self::assertSame('Qualification', $api['exportJobTitle']);
        self::assertSame('DDWIN SOLUTIONS', $api['user']['companyName']);
    }

    public function testResolveIncludesAllFieldsWhenSet(): void
    {
        $user = new User();
        $user->setEmail('brand@example.com');
        $prefs = new UserPreferences();
        $prefs->setUser($user);
        $prefs->setFirstName('Test');
        $prefs->setLastName('Berligue');
        $prefs->setExportDisplayName('TESTBERLIGUE');
        $prefs->setExportJobTitle('Qualification');
        $prefs->setExportCompanyName('DDWIN SOLUTIONS');
        $prefs->setExportPdfFooter('Document confidentiel');

        $this->repository
            ->method('getOrCreateForUser')
            ->willReturn($prefs);

        $view = $this->resolver->resolveForUser($user);
        $api = $view->toApiArray();

        self::assertSame('TESTBERLIGUE', $api['exportDisplayName']);
        self::assertSame('Document confidentiel', $api['exportPdfFooter']);
        self::assertSame('Test Berligue', $api['profileDisplayName']);
        self::assertSame(
            ['TESTBERLIGUE', 'Qualification', 'DDWIN SOLUTIONS'],
            $view->user?->headerLines(),
        );
    }
}
