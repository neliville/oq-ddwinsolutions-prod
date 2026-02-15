<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:users:export-json',
    description: 'Exporte la liste des utilisateurs au format JSON avec les informations essentielles et leur état.',
)]
final class ExportUsersJsonCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Fichier de sortie (sinon stdout)')
            ->addOption('pretty', 'p', InputOption::VALUE_NONE, 'JSON formaté (indentation)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);

        $data = array_map(function (User $user): array {
            $roles = $user->getRoles();
            $estAdmin = \in_array('ROLE_ADMIN', $roles, true);

            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $roles,
                'etat' => $estAdmin ? 'admin' : 'utilisateur',
                'created_at' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $users);

        $payload = [
            'exported_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'total' => \count($data),
            'utilisateurs' => $data,
        ];

        $flags = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE;
        if ($input->getOption('pretty')) {
            $flags |= \JSON_PRETTY_PRINT;
        }
        $json = json_encode($payload, $flags);

        $outputFile = $input->getOption('output');
        if ($outputFile !== null && $outputFile !== '') {
            if (file_put_contents($outputFile, $json) === false) {
                $io->error(sprintf('Impossible d\'écrire dans "%s".', $outputFile));
                return Command::FAILURE;
            }
            $io->success(sprintf('%d utilisateur(s) exporté(s) dans "%s".', \count($data), $outputFile));
            return Command::SUCCESS;
        }

        $output->writeln($json);
        return Command::SUCCESS;
    }
}
