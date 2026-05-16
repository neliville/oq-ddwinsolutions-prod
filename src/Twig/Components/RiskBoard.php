<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Qse\Risk\ViewModel\RiskBoardFilters;
use App\Qse\Risk\ViewModel\RiskBoardViewModel;
use App\Qse\Risk\ViewModel\RiskBoardViewModelFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class RiskBoard
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $status = '';

    #[LiveProp(writable: true)]
    public string $criticality = '';

    #[LiveProp(writable: true)]
    public string $sort = RiskBoardFilters::SORT_RECENT;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __construct(
        private readonly RiskBoardViewModelFactory $factory,
        private readonly Security $security,
    ) {
    }

    public function getViewModel(): RiskBoardViewModel
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Utilisateur requis pour le board risques.');
        }

        return $this->factory->build(
            $user,
            RiskBoardFilters::fromLiveProps(
                $this->search,
                $this->status,
                $this->criticality,
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
        $this->criticality = '';
        $this->sort = RiskBoardFilters::SORT_RECENT;
        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(int $targetPage): void
    {
        $this->page = max(1, $targetPage);
    }
}
