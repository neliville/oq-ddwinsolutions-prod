<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class IshikawaApiRouteDuplicationTest extends KernelTestCase
{
    public function testPostSaveRouteIsRegisteredOnce(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        $collection = $router->getRouteCollection();

        $matches = [];
        foreach ($collection->all() as $name => $route) {
            if ($route->getPath() === '/api/ishikawa/save' && \in_array('POST', $route->getMethods(), true)) {
                $matches[] = $name;
            }
        }

        self::assertCount(
            1,
            $matches,
            'Une seule route POST /api/ishikawa/save attendue ; trouvées : ' . implode(', ', $matches)
        );
    }
}
