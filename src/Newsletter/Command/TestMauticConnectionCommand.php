<?php

declare(strict_types=1);

namespace App\Newsletter\Command;

use App\Newsletter\Exception\MauticApiException;
use App\Newsletter\Infrastructure\MauticClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:newsletter:test-mautic',
    description: 'Teste la connexion à l\'API Mautic et la création d\'un contact de test',
)]
final class TestMauticConnectionCommand extends Command
{
    public function __construct(
        private readonly MauticClient $mauticClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $testEmail = 'test-newsletter-' . uniqid() . '@example.com';
        $payload = [
            'email' => $testEmail,
            'firstname' => 'Test',
            'tags' => ['newsletter'],
            'interet_principal' => ['outils'],
            'fonction' => 'consultant',
            'taille_entreprise1' => '1',
        ];

        $io->info('Envoi d\'un contact de test vers Mautic...');
        $io->text('Payload: ' . json_encode($payload, \JSON_PRETTY_PRINT));

        try {
            $response = $this->mauticClient->createOrUpdateContact($payload);
            $io->success('Connexion Mautic OK ! Contact créé.');
            $io->text('Réponse: ' . json_encode($response, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
            return Command::SUCCESS;
        } catch (MauticApiException $e) {
            $io->error(sprintf('Erreur Mautic (HTTP %d): %s', $e->getStatusCode(), $e->getMessage()));
            if ($e->getResponseBody()) {
                $io->text('Réponse API: ' . $e->getResponseBody());
            }
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $io->error('Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
