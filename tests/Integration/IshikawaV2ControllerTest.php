<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

final class IshikawaV2ControllerTest extends WebTestCaseWithDatabase
{
    public function testIshikawaV2OkWhenAnonymous(): void
    {
        $this->client->request('GET', 'https://localhost/ishikawa-v2');
        self::assertResponseIsSuccessful();
    }

    public function testIshikawaV2OkWhenAuthenticated(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);
        $this->client->request('GET', 'https://localhost/ishikawa-v2');
        self::assertResponseIsSuccessful();
    }
}
