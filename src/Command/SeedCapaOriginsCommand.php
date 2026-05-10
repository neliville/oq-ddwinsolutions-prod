<?php

declare(strict_types=1);

namespace App\Command;

use App\Qse\Service\CapaSystemOriginSeeder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:qse:seed-capa-origins', description: 'Insère ou met à jour les origines CAPA système (idempotent).')]
final class SeedCapaOriginsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CapaSystemOriginSeeder $capaSystemOriginSeeder,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $n = $this->capaSystemOriginSeeder->seed($this->entityManager);
        $io->success(sprintf('Origines CAPA système à jour (%d nouvelle(s) ligne(s) persistée(s)).', $n));

        return Command::SUCCESS;
    }
}
