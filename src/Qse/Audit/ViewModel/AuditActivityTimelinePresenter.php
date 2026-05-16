<?php

declare(strict_types=1);

namespace App\Qse\Audit\ViewModel;

use App\Entity\Qse\AuditActivityLog;
use App\Qse\Enum\AuditVerdict;

/**
 * Présentation métier des entrées de journal d'audit (réutilisable cockpit + board).
 */
final class AuditActivityTimelinePresenter
{
    /**
     * @return array{
     *     action: string,
     *     time: string,
     *     date: string,
     *     iso8601: string,
     *     relative: string,
     *     title: string,
     *     detail: ?string,
     *     icon: string,
     *     tone: string,
     *     actor: ?string,
     *     auditId: ?int,
     *     auditLabel: ?string
     * }
     */
    public function present(AuditActivityLog $log): array
    {
        $createdAt = $log->getCreatedAt();
        $action = $log->getAction();
        $payload = $log->getPayload() ?? [];
        $req = $log->getAuditEvaluation()?->getRequirement();
        $isoArticle = $req?->getIsoArticle() ?? (\is_string($payload['iso_article'] ?? null) ? $payload['iso_article'] : null);

        $verdictValue = \is_string($payload['verdict'] ?? null) ? $payload['verdict'] : null;
        $verdict = $verdictValue !== null ? AuditVerdict::tryFrom($verdictValue) : null;

        [$title, $detail, $icon, $tone] = $this->resolveActionPresentation($action, $verdict, $isoArticle);

        $actor = null;
        $user = $log->getActor();
        if ($user !== null) {
            $email = (string) ($user->getEmail() ?? '');
            if ($email !== '') {
                $atPos = mb_strpos($email, '@');
                $actor = $atPos !== false ? mb_substr($email, 0, $atPos) : $email;
            }
        }

        $audit = $log->getAudit();
        $auditLabel = null;
        if ($audit !== null) {
            $company = $audit->getCompanyName() ?? 'Audit';
            $date = $audit->getAuditedAt()?->format('d/m/Y') ?? '';
            $auditLabel = $date !== '' ? $company . ' — ' . $date : $company;
        }

        return [
            'action' => $action,
            'time' => $createdAt->format('H:i'),
            'date' => $createdAt->format('d/m/Y'),
            'iso8601' => $createdAt->format(\DateTimeInterface::ATOM),
            'relative' => $this->formatRelative($createdAt),
            'title' => $title,
            'detail' => $detail,
            'icon' => $icon,
            'tone' => $tone,
            'actor' => $actor,
            'auditId' => $audit?->getId(),
            'auditLabel' => $auditLabel,
        ];
    }

    private function formatRelative(\DateTimeImmutable $at): string
    {
        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $at->getTimestamp();
        if ($diff < 60) {
            return 'À l’instant';
        }
        if ($diff < 3600) {
            $m = (int) floor($diff / 60);

            return $m <= 1 ? 'Il y a 1 min' : sprintf('Il y a %d min', $m);
        }
        if ($diff < 86400) {
            $h = (int) floor($diff / 3600);

            return $h <= 1 ? 'Il y a 1 h' : sprintf('Il y a %d h', $h);
        }
        if ($diff < 604800) {
            $d = (int) floor($diff / 86400);

            return $d <= 1 ? 'Hier' : sprintf('Il y a %d j', $d);
        }

        return $at->format('d/m/Y');
    }

    /**
     * @return array{0: string, 1: ?string, 2: string, 3: string}
     */
    private function resolveActionPresentation(string $action, ?AuditVerdict $verdict, ?string $isoArticle): array
    {
        $articleSuffix = $isoArticle !== null && $isoArticle !== '' ? sprintf('Chapitre %s', $isoArticle) : null;

        switch ($action) {
            case 'evaluation_saved':
                $title = $articleSuffix !== null ? $articleSuffix . ' mis à jour' : 'Évaluation enregistrée';

                return match ($verdict) {
                    AuditVerdict::CONFORM => [$title, 'Conformité enregistrée', 'lucide:circle-check', 'success'],
                    AuditVerdict::OBSERVATION => [$title, 'Observation enregistrée', 'lucide:eye', 'info'],
                    AuditVerdict::TO_REVIEW => [$title, 'Marqué à revoir', 'lucide:circle-help', 'warning'],
                    AuditVerdict::MINOR_NC => [$title, 'Non-conformité mineure détectée', 'lucide:triangle-alert', 'warning'],
                    AuditVerdict::MAJOR_NC => [$title, 'Non-conformité majeure détectée', 'lucide:shield-alert', 'danger'],
                    AuditVerdict::NOT_APPLICABLE => [$title, 'Marqué non applicable', 'lucide:circle-slash', 'neutral'],
                    AuditVerdict::NOT_EVALUATED, null => [$title, 'Évaluation initialisée', 'lucide:circle-dashed', 'neutral'],
                };

            case 'capa_created':
                return ['CAPA créée', $articleSuffix !== null ? 'À partir du ' . lcfirst($articleSuffix) : 'Action corrective lancée', 'lucide:clipboard-list', 'info'];

            case 'audit_created':
                return ['Audit créé', null, 'lucide:file-plus', 'info'];

            case 'audit_validated':
                return ['Audit validé', 'Synthèse verrouillée', 'lucide:shield-check', 'success'];

            case 'audit_archived':
                return ['Audit archivé', null, 'lucide:archive', 'neutral'];

            case 'audit_duplicated':
                return ['Audit dupliqué', null, 'lucide:copy', 'info'];

            case 'audit_shared':
                return ['Audit partagé', null, 'lucide:share-2', 'info'];

            default:
                $humanized = ucfirst(str_replace('_', ' ', $action));

                return [$humanized, $articleSuffix, 'lucide:activity', 'neutral'];
        }
    }
}
