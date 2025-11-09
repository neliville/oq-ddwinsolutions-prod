<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ErrorPageTest extends WebTestCase
{
    public function test404PageDisplaysCustomContent(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/_error/404');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSelectorTextContains('.error-page__title', 'Oups, page introuvable');
        $this->assertSelectorExists('.error-page__actions a.btn.btn-primary');
    }

    public function test500PageDisplaysFallbackTemplate(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/_error/500');

        $this->assertResponseStatusCodeSame(500);
        $this->assertSelectorTextContains('.error-page__title', 'Nous rencontrons un problème');
        $this->assertSelectorExists('.error-page__code');
    }

    public function test403PageDisplaysGuidance(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/_error/403');

        $this->assertResponseStatusCodeSame(403);
        $this->assertSelectorTextContains('.error-page__title', 'Accès restreint');
    }
}
