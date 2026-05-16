<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Qse\Audit\ViewModel\AuditBoardFilters;
use App\Qse\Audit\ViewModel\AuditBoardViewModel;
use App\Qse\Audit\ViewModel\AuditBoardViewModelFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class AuditBoard
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $status = '';

    #[LiveProp(writable: true)]
    public string $standardCode = '';

    #[LiveProp(writable: true)]
    public string $auditor = '';

    #[LiveProp(writable: true)]
    public string $complianceMin = '';

    #[LiveProp(writable: true)]
    public string $dateFrom = '';

    #[LiveProp(writable: true)]
    public string $dateTo = '';

    #[LiveProp(writable: true)]
    public string $sort = AuditBoardFilters::SORT_RECENT;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __construct(
        private readonly AuditBoardViewModelFactory $factory,
        private readonly Security $security,
    ) {
    }

    public function getViewModel(): AuditBoardViewModel
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Utilisateur requis pour le board audit.');
        }

        return $this->factory->build(
            $user,
            AuditBoardFilters::fromLiveProps(
                $this->search,
                $this->status,
                $this->standardCode,
                $this->auditor,
                $this->complianceMin,
                $this->dateFrom,
                $this->dateTo,
                $this->sort,
                $this->page,
            ),
        );
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->standardCode = '';
        $this->auditor = '';
        $this->complianceMin = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sort = AuditBoardFilters::SORT_RECENT;
        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(int $targetPage): void
    {
        $this->page = max(1, $targetPage);
    }
}
