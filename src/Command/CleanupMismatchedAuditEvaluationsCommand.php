<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Qse\AuditEvaluation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:qse:cleanup-mismatched-audit-evaluations',
    description: 'Liste ou supprime les évaluations d’audit dont l’exigence n’appartient pas au même référentiel que l’audit (données incohérentes).',
)]
final class CleanupMismatchedAuditEvaluationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Limiter aux audits dont le propriétaire a cet e-mail');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Supprimer réellement les lignes (sans cette option : affichage seul)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getOption('email');
        $email = \is_string($email) && $email !== '' ? $email : null;
        $force = (bool) $input->getOption('force');

        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(AuditEvaluation::class, 'e')
            ->join('e.audit', 'a')
            ->join('e.requirement', 'r')
            ->where('a.auditStandard <> r.auditStandard');

        if ($email !== null) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user instanceof User) {
                $io->warning('Aucun utilisateur avec l’e-mail : ' . $email);

                return Command::SUCCESS;
            }
            $io->writeln(sprintf('Utilisateur trouvé : id=%d, e-mail=%s', $user->getId(), $user->getEmail()));
            $qb->andWhere('a.owner = :owner')->setParameter('owner', $user);
        }

        /** @var list<AuditEvaluation> $list */
        $list = $qb->getQuery()->getResult();
        $n = \count($list);

        if ($n === 0) {
            $io->success('Aucune évaluation incohérente (exigence d’un autre référentiel que l’audit).');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d évaluation(s) incohérente(s) (audit.standard ≠ requirement.standard).', $n));
        foreach ($list as $ev) {
            $audit = $ev->getAudit();
            $req = $ev->getRequirement();
            $io->writeln(sprintf(
                '  id_eval=%d audit_id=%s req_id=%s audit_std=%s req_std=%s',
                (int) $ev->getId(),
                $audit?->getId() !== null ? (string) $audit->getId() : '?',
                $req?->getId() !== null ? (string) $req->getId() : '?',
                $audit?->getAuditStandard()?->getCode() ?? '?',
                $req?->getAuditStandard()?->getCode() ?? '?',
            ));
        }

        if (!$force) {
            $io->note('Aucune suppression (mode lecture). Relancez avec --force pour supprimer ces évaluations (et constats liés en cascade).');

            return Command::SUCCESS;
        }

        foreach ($list as $ev) {
            $this->entityManager->remove($ev);
        }
        $this->entityManager->flush();
        $io->success(sprintf('%d évaluation(s) supprimée(s).', $n));

        return Command::SUCCESS;
    }
}
