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
    name: 'app:newsletter:list-mautic-fields',
    description: 'Liste les champs de contact Mautic et leurs options (pour configurer les valeurs par défaut)',
)]
final class ListMauticFieldsCommand extends Command
{
    public function __construct(
        private readonly MauticClient $mauticClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Récupération des champs Mautic...');

        try {
            $data = $this->mauticClient->getContactFields();
        } catch (MauticApiException $e) {
            $io->error(sprintf('Erreur Mautic (HTTP %d): %s', $e->getStatusCode(), $e->getMessage()));
            if ($e->getResponseBody()) {
                $io->text('Réponse: ' . $e->getResponseBody());
            }
            return Command::FAILURE;
        }

        $fields = $data['fields'] ?? $data;
        if (!\is_array($fields)) {
            $io->warning('Format de réponse inattendu.');
            $io->text(json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE));
            return Command::SUCCESS;
        }

        $requiredFields = [];
        foreach ($fields as $field) {
            if (!\is_array($field)) {
                continue;
            }
            $alias = $field['alias'] ?? $field['id'] ?? '?';
            $label = $field['label'] ?? $alias;
            $type = $field['type'] ?? 'text';
            $isRequired = $field['isRequired'] ?? false;

            if (!$isRequired) {
                continue;
            }

            $options = $field['properties']['list'] ?? $field['properties'] ?? null;
            $optionsStr = '';
            if (\is_array($options)) {
                $values = [];
                foreach ($options as $k => $v) {
                    $values[] = \is_string($v) ? $v : (string) $k;
                }
                $optionsStr = ' → Options: ' . implode(', ', array_slice($values, 0, 10));
                if (\count($values) > 10) {
                    $optionsStr .= '...';
                }
            }

            $requiredFields[] = sprintf('  - %s (%s) %s', $alias, $type, $optionsStr);
        }

        if (empty($requiredFields)) {
            $io->success('Aucun champ obligatoire trouvé.');
            return Command::SUCCESS;
        }

        $io->title('Champs obligatoires dans Mautic');
        $io->listing($requiredFields);
        $io->note('Utilisez ces valeurs (alias et options) dans NewsletterSubscriber pour les inscriptions newsletter.');

        return Command::SUCCESS;
    }
}
