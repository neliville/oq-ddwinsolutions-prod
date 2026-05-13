<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'cockpit QHSE');
    }

    public function testHomePageContainsExpectedSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $siteFooter = $crawler->filter('footer')->reduce(
            static fn ($node): bool => str_contains($node->text(), "Outils d'Analyse Gratuits")
        )->first();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav');
        $this->assertSelectorExists('footer');
        $this->assertGreaterThan(0, $siteFooter->count());
        $this->assertStringContainsString("Outils d'Analyse Gratuits", $siteFooter->text());
        $this->assertGreaterThan(0, $crawler->filter('#outils')->count());
        $this->assertGreaterThan(0, $crawler->filter('#fonctionnalites')->count());
        $this->assertGreaterThan(0, $crawler->filter('#newsletter')->count());
    }

    public function testHomePageHasKeyContentAndSecondaryToolEntries(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $dashboardHeading = $crawler->filter('#home-dashboard-heading');
        $tools = $crawler->filter('#outils');
        $toolLinks = $tools->filter('.tool-card a[href]');
        $toolHrefs = $toolLinks->extract(['href']);
        $toolAccessibleNames = $toolLinks->each(
            static function ($link): string {
                $ariaLabel = $link->attr('aria-label');
                if (is_string($ariaLabel) && '' !== trim($ariaLabel)) {
                    return trim($ariaLabel);
                }

                return trim(preg_replace('/\s+/', ' ', $link->text()) ?? '');
            }
        );
        $expectedToolPaths = [
            $router->generate('app_ishikawa_index'),
            $router->generate('app_fivewhy_index'),
            $router->generate('app_qqoqccp_index'),
            $router->generate('app_amdec_index'),
            $router->generate('app_eightd_index'),
            $router->generate('app_pareto_index'),
        ];

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $dashboardHeading->count());
        $this->assertStringContainsString('cockpit', strtolower(trim($dashboardHeading->text())));
        $this->assertStringContainsString('priorités', trim($dashboardHeading->text()));
        $this->assertSame(1, $tools->count());
        $this->assertCount(count($expectedToolPaths), $toolLinks);
        $this->assertSame($expectedToolPaths, $toolHrefs);
        $this->assertCount(count($expectedToolPaths), array_unique($toolAccessibleNames));
        $this->assertSame(0, $tools->selectLink('Explorer tous les outils')->count());
    }

    public function testHomePageHeroCreatesNeedForCockpit(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $hero = $crawler->filter('section[aria-labelledby="home-hero-heading"]');
        $heroTitle = $hero->filter('h1');
        $heroSubtitle = $hero->filter('.hero-subtitle');
        $heroLinks = $hero->filter('a[href]');
        $heroHrefs = $heroLinks->extract(['href']);
        $heroText = preg_replace('/\s+/', ' ', $hero->text()) ?? '';
        $toolCatalogPath = $router->generate('app_outils_index');
        $platformEntryPaths = [
            $router->generate('app_register'),
            $router->generate('app_login'),
            $router->generate('app_dashboard_index'),
        ];

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $hero->count());
        $this->assertSame(1, $heroTitle->count());
        $this->assertSame(1, $heroSubtitle->count());
        $this->assertStringContainsString('cockpit qhse', strtolower(trim($heroTitle->text())));
        $this->assertStringContainsString('priorités', strtolower(trim($heroTitle->text())));
        $this->assertStringContainsString('cockpit', strtolower($heroText));
        $this->assertMatchesRegularExpression('/quotidien|chaque jour/i', $heroText);
        $this->assertStringContainsString('charge mentale', $heroText);
        $this->assertMatchesRegularExpression('/m[êe]me vue|vue unique/i', $heroText);
        $this->assertStringContainsString('priorités', $heroText);
        $this->assertStringContainsString('alertes', $heroText);
        $this->assertStringContainsString('audits', strtolower($heroText));
        $this->assertStringContainsString('CAPA', $heroText);
        $this->assertStringContainsString('Risques', $heroText);
        $this->assertStringContainsString('échéances', $heroText);
        $this->assertGreaterThan(0, $hero->filter('.hero-landing-preview[aria-hidden="true"]')->count());
        $this->assertSame(
            0,
            $hero->filter('.home-hero-preview__tile')->count(),
            'Le preview Hero doit montrer un cockpit unifié, pas trois mini-cartes marketing.'
        );
        $this->assertSame(
            0,
            $hero->selectLink('Me connecter')->count(),
            'Le CTA secondaire froid "Me connecter" ne doit pas revenir dans le Hero.'
        );
        $this->assertNotContains($toolCatalogPath, $heroHrefs);
        $this->assertTrue(
            $this->hasPlatformEntryAction($heroHrefs, $platformEntryPaths),
            'Le Hero doit exposer au moins une action d’entrée plateforme.'
        );
    }

    public function testHomePageFollowsPlatformFirstSectionOrder(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $sectionIds = $crawler->filter('.home-page > section')->extract(['id']);

        $heroIndex = array_search('hero', $sectionIds, true);
        $dashboardIndex = array_search('dashboard-focus', $sectionIds, true);
        $socialProofIndex = array_search('preuve-sociale', $sectionIds, true);
        $workflowIndex = array_search('workflow', $sectionIds, true);
        $toolsIndex = array_search('outils', $sectionIds, true);

        $this->assertResponseIsSuccessful();
        $this->assertNotFalse($heroIndex);
        $this->assertNotFalse($dashboardIndex);
        $this->assertNotFalse($socialProofIndex);
        $this->assertNotFalse($workflowIndex);
        $this->assertNotFalse($toolsIndex);
        $this->assertSame(0, $heroIndex);
        $this->assertSame($heroIndex + 1, $dashboardIndex);
        $this->assertGreaterThan($dashboardIndex, $toolsIndex);
        $this->assertGreaterThan($socialProofIndex, $toolsIndex);
        $this->assertGreaterThan($workflowIndex, $toolsIndex);
    }

    public function testHomePageNoLongerRendersStandaloneRedundantSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $crawler->filter('section[aria-labelledby="home-audits-heading"]')->count());
        $this->assertSame(0, $crawler->filter('section[aria-labelledby="home-capa-heading"]')->count());
        $this->assertSame(0, $crawler->filter('section[aria-labelledby="home-personas-heading"]')->count());
        $this->assertSame(0, $crawler->filter('section[aria-labelledby="home-before-after-heading"]')->count());
    }

    public function testHomePageShowsWhatTheCockpitCentralizes(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $scope = $crawler->filter('#fonctionnalites');
        $heading = $crawler->filter('#home-value-heading');
        $headingText = preg_replace('/\s+/', ' ', $heading->text()) ?? '';
        $scopeText = preg_replace('/\s+/', ' ', $scope->text()) ?? '';
        $scopeLinks = $scope->filter('a[href]');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $scope->count());
        $this->assertSame(1, $heading->count());
        $this->assertStringContainsString('cockpit', strtolower($headingText));
        $this->assertStringContainsString('centralise', strtolower($headingText));
        $this->assertStringContainsString('piloter', strtolower($headingText));
        $this->assertStringContainsString('Analyses', $scopeText);
        $this->assertStringContainsString('Audits ISO', $scopeText);
        $this->assertStringContainsString('CAPA', $scopeText);
        $this->assertStringContainsString('Risques', $scopeText);
        $this->assertStringNotContainsString('Dashboard', $scopeText);
        $this->assertSame(
            0,
            $scopeLinks->count(),
            'La section plateforme consolidée ne doit plus exposer de CTA ou liens par carte.'
        );
    }

    public function testHomePageShowsOneConnectedOperatingModel(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $workflow = $crawler->filter('#workflow');
        $heading = $crawler->filter('#home-workflow-heading');
        $steps = $workflow->filter('ol > li');
        $headingText = preg_replace('/\s+/', ' ', $heading->text()) ?? '';
        $workflowText = preg_replace('/\s+/', ' ', $workflow->text()) ?? '';
        $stepTitles = $steps->each(
            static function ($step): string {
                $stepText = preg_replace('/\s+/', ' ', trim($step->text())) ?? '';
                preg_match('/^\d+\s+(\p{L}+)/u', $stepText, $matches);

                return $matches[1] ?? '';
            }
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $workflow->count());
        $this->assertSame(1, $heading->count());
        $this->assertStringContainsString('cockpit', strtolower($headingText));
        $this->assertStringContainsString('structure', strtolower($headingText));
        $this->assertStringContainsString('qhse', strtolower($headingText));
        $this->assertStringContainsString('Avant', $workflowText);
        $this->assertStringContainsString('Avec cockpit', $workflowText);
        $this->assertCount(5, $stepTitles, 'La séquence métier doit contenir exactement cinq étapes.');
        $this->assertSame(
            ['Analyser', 'Auditer', 'Traiter', 'Maîtriser', 'Piloter'],
            $stepTitles,
            'Les étapes métier doivent rester ordonnées de l’analyse au pilotage.'
        );
        $this->assertMatchesRegularExpression('/actions?/i', $workflowText);
        $this->assertMatchesRegularExpression('/risques?/i', $workflowText);
        $this->assertSame(
            0,
            $workflow->filter('a[href]')->count(),
            'La section fonctionnement ne doit pas réintroduire de CTA.'
        );
    }

    public function testHomePageExposesPlatformEntryActionsInHeroAndFinalCta(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $toolCatalogPath = static::getContainer()->get('router')->generate('app_outils_index');
        $platformEntryPaths = [
            $router->generate('app_register'),
            $router->generate('app_login'),
            $router->generate('app_dashboard_index'),
        ];
        $heroCtaLinks = $crawler->filter('section[aria-labelledby="home-hero-heading"] a[href]');
        $finalCtaLinks = $crawler->filter('section[aria-labelledby="home-final-cta-heading"] a[href]');
        $heroCtaHrefs = $heroCtaLinks->extract(['href']);
        $finalCtaHrefs = $finalCtaLinks->extract(['href']);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $heroCtaLinks->count());
        $this->assertGreaterThan(0, $finalCtaLinks->count());
        $this->assertNotContains($toolCatalogPath, $heroCtaHrefs);
        $this->assertNotContains($toolCatalogPath, $finalCtaHrefs);
        $this->assertTrue(
            $this->hasPlatformEntryAction($heroCtaHrefs, $platformEntryPaths),
            'Le hero doit exposer au moins une action d’entrée plateforme, distincte du catalogue outils.'
        );
        $this->assertTrue(
            $this->hasPlatformEntryAction($finalCtaHrefs, $platformEntryPaths),
            'Le CTA final doit exposer au moins une action d’entrée plateforme, distincte du catalogue outils.'
        );
    }

    public function testHomePageSocialProofPrioritizesProfessionalCredibility(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $socialProof = $crawler->filter('#preuve-sociale');
        $socialProofText = mb_strtolower(preg_replace('/\s+/', ' ', $socialProof->text()) ?? '');
        $statValues = $socialProof->filter('.social-stat__value')->each(
            static fn ($node): string => trim(preg_replace('/\s+/', ' ', $node->text()) ?? '')
        );
        $statLabels = $socialProof->filter('.social-stat__label')->each(
            static fn ($node): string => trim(preg_replace('/\s+/', ' ', $node->text()) ?? '')
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $socialProof->count());
        $this->assertCount(3, $statValues);
        $this->assertCount(3, array_unique($statValues));
        $this->assertCount(3, $statLabels);
        $this->assertMatchesRegularExpression('/usage/i', $statValues[0] ?? '');
        $this->assertMatchesRegularExpression('/conformité|audit/i', implode(' ', $statValues));
        $this->assertMatchesRegularExpression('/export/i', implode(' ', $statValues));
        $this->assertStringContainsString('équipes qhse', $socialProofText);
        $this->assertStringContainsString('audit', $socialProofText);
        $this->assertMatchesRegularExpression('/pdf|synth[èe]ses/i', implode(' ', $statLabels));
        $this->assertStringContainsString('pilotage exploitable', $socialProofText);
        $this->assertStringNotContainsString('0€', $socialProofText);
        $this->assertStringNotContainsString('sans carte bancaire', $socialProofText);
        $this->assertStringNotContainsString('6 outils gratuits', $socialProofText);
    }

    public function testHomePageFreeToolsSectionRemainsSecondaryAcquisition(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $tools = $crawler->filter('#outils');
        $toolsHeading = $tools->filter('#home-outils-heading');
        $toolsText = mb_strtolower(preg_replace('/\s+/', ' ', $tools->text()) ?? '');
        $toolLinks = $tools->filter('.tool-card a[href]');
        $toolHrefs = $toolLinks->extract(['href']);
        $sectionIds = $crawler->filter('.home-page > section')->extract(['id']);
        $socialProofIndex = array_search('preuve-sociale', $sectionIds, true);
        $workflowIndex = array_search('workflow', $sectionIds, true);
        $toolsIndex = array_search('outils', $sectionIds, true);
        $forbiddenPlatformPaths = [
            $router->generate('app_register'),
            $router->generate('app_login'),
            $router->generate('app_dashboard_index'),
            $router->generate('app_qse_audit_index'),
        ];
        $expectedToolPaths = [
            $router->generate('app_ishikawa_index'),
            $router->generate('app_fivewhy_index'),
            $router->generate('app_qqoqccp_index'),
            $router->generate('app_amdec_index'),
            $router->generate('app_eightd_index'),
            $router->generate('app_pareto_index'),
        ];

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $tools->count());
        $this->assertSame(1, $toolsHeading->count());
        $this->assertMatchesRegularExpression('/outils gratuits|premier besoin/i', $toolsHeading->text());
        $this->assertStringContainsString('premier besoin', $toolsText);
        $this->assertStringContainsString('outils gratuits', $toolsText);
        $this->assertMatchesRegularExpression('/porte d[’\']entrée|point d[’\']entrée|entrée simple/i', $toolsText);
        $this->assertStringContainsString('plateforme', $toolsText);
        $this->assertStringNotContainsString('le plus utilisé', $toolsText);
        $this->assertCount(count($expectedToolPaths), $toolLinks);
        $this->assertSame($expectedToolPaths, $toolHrefs);
        $this->assertSame(0, $tools->selectLink('Explorer tous les outils')->count());
        $this->assertNotFalse($socialProofIndex);
        $this->assertNotFalse($workflowIndex);
        $this->assertNotFalse($toolsIndex);
        $this->assertGreaterThan($socialProofIndex, $toolsIndex);
        $this->assertGreaterThan($workflowIndex, $toolsIndex);
        $this->assertFalse(
            $this->hasPlatformEntryAction($toolHrefs, $forbiddenPlatformPaths),
            'La section outils doit rester une acquisition secondaire sans CTA plateforme.'
        );
    }

    public function testHomePageFinalCtaExposesAnonymousPlatformActions(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $finalCta = $crawler->filter('section[aria-labelledby="home-final-cta-heading"]');
        $finalCtaLinks = $finalCta->filter('a[href]');
        $finalCtaHrefs = $finalCtaLinks->extract(['href']);
        $finalCtaLabels = $finalCtaLinks->each(
            static fn ($link): string => trim(preg_replace('/\s+/', ' ', $link->text()) ?? '')
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $finalCta->count());
        $this->assertSame(
            [$router->generate('app_register'), $router->generate('app_login')],
            $finalCtaHrefs
        );
        $this->assertSame(['Structurer mon QHSE', 'Me connecter'], $finalCtaLabels);
        $this->assertNotContains($router->generate('app_outils_index'), $finalCtaHrefs);
    }

    public function testHomePageFinalCtaExposesAuthenticatedPlatformActions(): void
    {
        $client = static::createClient();
        $user = (new User())
            ->setEmail('task6-final-cta@example.com')
            ->setPassword('test-password')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $finalCta = $crawler->filter('section[aria-labelledby="home-final-cta-heading"]');
        $finalCtaLinks = $finalCta->filter('a[href]');
        $finalCtaHrefs = $finalCtaLinks->extract(['href']);
        $finalCtaLabels = $finalCtaLinks->each(
            static fn ($link): string => trim(preg_replace('/\s+/', ' ', $link->text()) ?? '')
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $finalCta->count());
        $this->assertSame(
            [$router->generate('app_dashboard_index'), $router->generate('app_qse_audit_index')],
            $finalCtaHrefs
        );
        $this->assertSame(['Accéder à mon cockpit QHSE', 'Ouvrir mes audits'], $finalCtaLabels);
        $this->assertNotContains($router->generate('app_outils_index'), $finalCtaHrefs);
    }

    public function testHomePageDashboardSectionSellsRecurrenceNotExistence(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $dashboard = $crawler->filter('#dashboard-focus');
        $dashboardLinks = $dashboard->filter('a[href]');
        $dashboardHrefs = $dashboardLinks->extract(['href']);
        $dashboardText = preg_replace('/\s+/', ' ', $dashboard->text()) ?? '';
        $loginPath = $router->generate('app_login');
        $dashboardPath = $router->generate('app_dashboard_index');
        $registerPath = $router->generate('app_register');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $dashboard->count());
        $this->assertStringContainsString('cockpit', strtolower($dashboardText));
        $this->assertStringContainsString('quotidien', strtolower($dashboardText));
        $this->assertStringContainsString('hebdomadaire', strtolower($dashboardText));
        $this->assertStringContainsString('charge mentale', $dashboardText);
        $this->assertStringContainsString('priorités', $dashboardText);
        $this->assertStringContainsString('audit', strtolower($dashboardText));
        $this->assertStringContainsString('CAPA', $dashboardText);
        $this->assertStringContainsString('risques', strtolower($dashboardText));
        $this->assertStringNotContainsString('vous revenez', strtolower($dashboardText));
        $this->assertStringNotContainsString('auquel vous revenez', strtolower($dashboardText));
        $this->assertSame(1, $dashboardLinks->count(), 'Le bloc dashboard-focus doit garder un seul lien discret.');
        $this->assertNotContains($registerPath, $dashboardHrefs, 'Le lien discret ne doit pas renvoyer vers une inscription agressive.');
        $this->assertTrue(
            in_array($dashboardHrefs[0] ?? null, [$loginPath, $dashboardPath], true),
            'Le lien discret doit mener vers une entree cohérente de type login/cockpit.'
        );
        $this->assertSame(
            0,
            $dashboard->selectLink('Créer un compte gratuit')->count(),
            'Le bloc récurrence ne doit pas redevenir un bloc de conversion agressif.'
        );
    }

    public function testHomePageDashboardFocusAnonymousUserGetsDiscreteLoginLink(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $dashboard = $crawler->filter('#dashboard-focus');
        $dashboardLinks = $dashboard->filter('a[href]');
        $dashboardHrefs = $dashboardLinks->extract(['href']);
        $dashboardLinkText = trim(preg_replace('/\s+/', ' ', $dashboardLinks->text()) ?? '');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $dashboardLinks->count());
        $this->assertSame([$router->generate('app_login')], $dashboardHrefs);
        $this->assertStringContainsString('cockpit', strtolower($dashboardLinkText));
    }

    public function testHomePageDashboardFocusAuthenticatedUserGetsCockpitLink(): void
    {
        $client = static::createClient();
        $user = (new User())
            ->setEmail('task3-dashboard@example.com')
            ->setPassword('test-password')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $dashboard = $crawler->filter('#dashboard-focus');
        $dashboardLinks = $dashboard->filter('a[href]');
        $dashboardHrefs = $dashboardLinks->extract(['href']);
        $dashboardLinkText = trim(preg_replace('/\s+/', ' ', $dashboardLinks->text()) ?? '');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $dashboardLinks->count());
        $this->assertSame([$router->generate('app_dashboard_index')], $dashboardHrefs);
        $this->assertStringContainsString('cockpit', strtolower($dashboardLinkText));
    }

    public function testHomePageFreeToolsSectionUsesProperHeadingHierarchy(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $tools = $crawler->filter('#outils');
        $sectionHeading = $tools->filter('h2');
        $cardHeadings = $tools->filter('.tool-card h3');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $tools->count());
        $this->assertSame(1, $sectionHeading->count());
        $this->assertSame(0, $tools->filter('.tool-card h4')->count());
        $this->assertCount($tools->filter('.tool-card')->count(), $cardHeadings);
    }

    /**
     * @param list<string> $hrefs
     * @param list<string> $platformEntryPaths
     */
    private function hasPlatformEntryAction(array $hrefs, array $platformEntryPaths): bool
    {
        foreach ($hrefs as $href) {
            if (in_array($href, $platformEntryPaths, true)) {
                return true;
            }
        }

        return false;
    }
}
