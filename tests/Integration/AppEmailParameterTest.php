<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Garantit que APP_EMAIL est résolu (requis pour le mailer et la réinitialisation de mot de passe).
 */
class AppEmailParameterTest extends KernelTestCase
{
    public function testAppEmailParameterIsResolved(): void
    {
        self::bootKernel();

        $email = self::getContainer()->getParameter('app.email');

        self::assertIsString($email);
        self::assertNotSame('', $email);
        self::assertStringContainsString('@', $email);
    }
}
