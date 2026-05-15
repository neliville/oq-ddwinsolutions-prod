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
        $this->assertSelectorTextContains('h1', 'qualité');
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
        $this->assertGreaterThan(0, $crawler->filter('#cockpit-qhse')->count());
        $this->assertGreaterThan(0, $crawler->filter('#fonctionnalites')->count());
        $this->assertGreaterThan(0, $crawler->filter('#newsletter')->count());
        $this->assertGreaterThan(0, $crawler->filter('#accompagnement')->count());
    }

    public function testHomePageHasKeyContentAndToolEntries(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $tools = $crawler->filter('#outils');
        $toolLinks = $tools->filter('.tool-card a[href]');
        $toolHrefs = $toolLinks->extract(['href']);
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
        $this->assertCount(count($expectedToolPaths), $toolLinks);
        $this->assertSame($expectedToolPaths, $toolHrefs);
        $this->assertSame(0, $tools->selectLink('Explorer tous les outils')->count());
    }

    public function testHomePageHeroPrioritizesFreeTools(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $hero = $crawler->filter('section[aria-labelledby="home-hero-heading"]');
        $heroTitle = trim($hero->filter('h1')->text());
        $heroHrefs = $hero->filter('a[href]')->extract(['href']);
        $heroText = preg_replace('/\s+/', ' ', $hero->text()) ?? '';
        $ishikawaPath = $router->generate('app_ishikawa_index');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $hero->count());
        $this->assertStringContainsString('gratuit', mb_strtolower($heroTitle));
        $this->assertStringContainsString('sans inscription', mb_strtolower($heroText));
        $this->assertTrue($this->hasHref($heroHrefs, [$ishikawaPath]));
        $this->assertStringNotContainsString('cockpit qhse', mb_strtolower($heroTitle));
        $this->assertGreaterThan(0, $hero->filter('.home-hero-tool-preview')->count());
        $this->assertSame(0, $hero->selectLink('Voir mon cockpit')->count());
    }

    public function testHomePageFollowsToolsFirstSectionOrder(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $sectionIds = $crawler->filter('.home-page > section')->extract(['id']);

        $heroIndex = array_search('hero', $sectionIds, true);
        $toolsIndex = array_search('outils', $sectionIds, true);
        $whyAccountIndex = array_search('pourquoi-compte', $sectionIds, true);
        $cockpitIndex = array_search('cockpit-qhse', $sectionIds, true);
        $socialProofIndex = array_search('preuve-sociale', $sectionIds, true);
        $accompagnementIndex = array_search('accompagnement', $sectionIds, true);
        $newsletterIndex = array_search('newsletter', $sectionIds, true);
        $faqIndex = array_search('faq', $sectionIds, true);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['hero', 'outils', 'pourquoi-compte', 'cockpit-qhse', 'preuve-sociale', 'accompagnement', 'newsletter', 'faq'], $sectionIds);
        $this->assertSame(0, $heroIndex);
        $this->assertSame(1, $toolsIndex);
        $this->assertLessThan($cockpitIndex, $toolsIndex, 'Les outils doivent précéder le cockpit.');
        $this->assertLessThan($socialProofIndex, $cockpitIndex, 'Le cockpit doit précéder la preuve sociale.');
        $this->assertLessThan($faqIndex, $newsletterIndex, 'La newsletter doit précéder la FAQ.');
        $this->assertNotFalse($whyAccountIndex);
        $this->assertNotFalse($accompagnementIndex);
    }

    public function testHomePageNoLongerRendersStandaloneRedundantSections(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $crawler->filter('#dashboard-focus')->count());
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
        $scopeText = preg_replace('/\s+/', ' ', $scope->text()) ?? '';

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $scope->count());
        $this->assertStringContainsString('cockpit', strtolower($heading->text()));
        $this->assertStringContainsString('Analyses', $scopeText);
        $this->assertStringContainsString('Audits ISO', $scopeText);
        $this->assertStringContainsString('CAPA', $scopeText);
        $this->assertStringContainsString('Risques', $scopeText);
        $this->assertSame(0, $scope->filter('a[href]')->count());
    }

    public function testHomePageShowsThreeStepOperatingModel(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $workflow = $crawler->filter('#workflow');
        $steps = $workflow->filter('ol > li');
        $stepTitles = $steps->each(
            static function ($step): string {
                $stepText = preg_replace('/\s+/', ' ', trim($step->text())) ?? '';
                preg_match('/^\d+\s+(.+?)(?:\s{2,}|$)/u', $stepText, $matches);

                return $matches[1] ?? '';
            }
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $workflow->count());
        $this->assertCount(3, $stepTitles);
        $this->assertStringContainsString('outil', mb_strtolower($stepTitles[0] ?? ''));
        $this->assertStringContainsString('exporter', mb_strtolower($stepTitles[1] ?? ''));
        $this->assertStringContainsString('cockpit', mb_strtolower($stepTitles[2] ?? ''));
    }

    public function testHomePageHeroAndCockpitCtaExposeExpectedActions(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $heroHrefs = $crawler->filter('section[aria-labelledby="home-hero-heading"] a[href]')->extract(['href']);
        $finalCtaHrefs = $crawler->filter('section[aria-labelledby="home-final-cta-heading"] a[href]')->extract(['href']);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->hasHref($heroHrefs, [$router->generate('app_ishikawa_index')]));
        $this->assertTrue($this->hasHref($heroHrefs, [$router->generate('app_register')]));
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_ishikawa_index')]));
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_register')]));
    }

    public function testHomePageSocialProofUsesFactualToolFirstCredibility(): void
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
        $this->assertSame(['6 outils', '0 €', 'PDF'], $statValues);
        $this->assertStringContainsString('méthodes terrain qse', mb_strtolower($statLabels[0] ?? ''));
        $this->assertStringContainsString('pas de carte bancaire', mb_strtolower($statLabels[1] ?? ''));
        $this->assertStringContainsString('exports prêts pour l\'audit', mb_strtolower($statLabels[2] ?? ''));
        $this->assertStringContainsString('ishikawa', $socialProofText);
        $this->assertStringContainsString('équipes', $socialProofText);
    }

    public function testHomePageFreeToolsSectionIsPrimaryAcquisition(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $tools = $crawler->filter('#outils');
        $toolsText = mb_strtolower(preg_replace('/\s+/', ' ', $tools->text()) ?? '');
        $sectionIds = $crawler->filter('.home-page > section')->extract(['id']);
        $toolsIndex = array_search('outils', $sectionIds, true);
        $forbiddenPlatformPaths = [
            $router->generate('app_register'),
            $router->generate('app_login'),
            $router->generate('app_dashboard_index'),
        ];

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $toolsIndex);
        $this->assertMatchesRegularExpression('/30 secondes|outils gratuits/i', $tools->filter('#home-outils-heading')->text());
        $this->assertStringContainsString('sans inscription', $toolsText);
        $this->assertStringContainsString('le plus utilisé', $toolsText);
        $this->assertGreaterThan(0, $tools->selectLink('Utiliser gratuitement')->count());
        $this->assertFalse($this->hasHref($tools->filter('.tool-card a[href]')->extract(['href']), $forbiddenPlatformPaths));
    }

    public function testHomePageFinalCtaExposesAnonymousPlatformActions(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $finalCtaHrefs = $crawler->filter('section[aria-labelledby="home-final-cta-heading"] a[href]')->extract(['href']);
        $finalCtaLabels = $crawler->filter('section[aria-labelledby="home-final-cta-heading"] a[href]')->each(
            static fn ($link): string => trim(preg_replace('/\s+/', ' ', $link->text()) ?? '')
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_register')]));
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_ishikawa_index')]));
        $this->assertContains('Centraliser mon QHSE dans le cockpit', $finalCtaLabels);
        $this->assertContains("Essayer un outil d'abord", $finalCtaLabels);
    }

    public function testHomePageFinalCtaExposesAuthenticatedPlatformActions(): void
    {
        $client = static::createClient();
        $user = (new User())
            ->setEmail('conversion-v2-final-cta@example.com')
            ->setPassword('test-password')
            ->setRoles(['ROLE_USER']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/');
        $router = static::getContainer()->get('router');
        $finalCtaHrefs = $crawler->filter('section[aria-labelledby="home-final-cta-heading"] a[href]')->extract(['href']);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_dashboard_index')]));
        $this->assertTrue($this->hasHref($finalCtaHrefs, [$router->generate('app_ishikawa_index')]));
    }

    public function testHomePageWhyAccountSectionEncouragesRegistration(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $whyAccount = $crawler->filter('#pourquoi-compte');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $whyAccount->count());
        $this->assertStringContainsString('compte gratuit', mb_strtolower($whyAccount->text()));
        $this->assertGreaterThan(0, $whyAccount->selectLink('Créer mon compte gratuit')->count());
    }

    public function testHomePageFreeToolsSectionUsesProperHeadingHierarchy(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $tools = $crawler->filter('#outils');

        $this->assertResponseIsSuccessful();
        $this->assertSame(1, $tools->filter('h2')->count());
        $this->assertCount($tools->filter('.tool-card')->count(), $tools->filter('.tool-card h3'));
    }

    public function testHomePageSeoMetaIsToolsFirst(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('title');
        $title = $crawler->filter('title')->text();
        $this->assertStringContainsString('Ishikawa', $title);
        $this->assertStringNotContainsString('Plateforme QHSE : pilotage', $title);
        $this->assertSelectorExists('meta[name="description"]');
        $description = $crawler->filter('meta[name="description"]')->attr('content');
        $this->assertStringContainsString('sans inscription', mb_strtolower($description ?? ''));
    }

    public function testHomePageFaqMentionsAllSixTools(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        $faqText = mb_strtolower($crawler->filter('#faq')->text());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('ishikawa', $faqText);
        $this->assertStringContainsString('pareto', $faqText);
        $this->assertStringContainsString('six outils', $faqText);
        $this->assertStringContainsString('sans inscription', $faqText);
    }

    /**
     * @param list<string> $hrefs
     * @param list<string> $paths
     */
    private function hasHref(array $hrefs, array $paths): bool
    {
        foreach ($hrefs as $href) {
            foreach ($paths as $path) {
                if ($href === $path || str_ends_with($href, $path)) {
                    return true;
                }
            }
        }

        return false;
    }
}
