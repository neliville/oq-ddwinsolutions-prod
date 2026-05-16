<?php

declare(strict_types=1);

namespace App\Tests\Unit\Qse;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Qse\Capa\ViewModel\CapaBoardFilters;
use App\Qse\Capa\ViewModel\CapaBoardViewModelFactory;
use App\Qse\Enum\CapaStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class CapaBoardViewModelFactoryTest extends WebTestCaseWithDatabase
{
    public function testBuildReturnsKpisAndPaginatedRows(): void
    {
        $user = $this->createTestUser('capa-vm-' . uniqid() . '@example.com', 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => 'autre']);
        $this->assertInstanceOf(CapaOrigin::class, $origin);

        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setOrigin($origin);
        $capa->setTitle('CAPA test board');
        $capa->setStatus(CapaStatus::A_VALIDER);
        $capa->setDueAt(new \DateTimeImmutable('+14 days'));
        $this->entityManager->persist($capa);
        $this->entityManager->flush();

        /** @var CapaBoardViewModelFactory $factory */
        $factory = static::getContainer()->get(CapaBoardViewModelFactory::class);
        $vm = $factory->build($user, new CapaBoardFilters());

        self::assertSame($vm->totalCount, $vm->kpis->totalCapas);
        self::assertGreaterThanOrEqual(1, $vm->kpis->totalCapas);
        self::assertGreaterThanOrEqual(1, $vm->kpis->openCapas);
        self::assertNotEmpty($vm->rows);
        self::assertStringContainsString('CAPA test board', $vm->rows[0]->capa->getTitle());
    }
}
