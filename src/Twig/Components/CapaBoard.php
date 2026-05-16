<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\User;
use App\Qse\Capa\ViewModel\CapaBoardFilters;
use App\Qse\Capa\ViewModel\CapaBoardViewModel;
use App\Qse\Capa\ViewModel\CapaBoardViewModelFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CapaBoard
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $status = '';

    #[LiveProp(writable: true)]
    public string $capaType = '';

    #[LiveProp(writable: true)]
    public string $criticality = '';

    #[LiveProp(writable: true)]
    public bool $overdueOnly = false;

    #[LiveProp(writable: true)]
    public string $dueFrom = '';

    #[LiveProp(writable: true)]
    public string $dueTo = '';

    #[LiveProp(writable: true)]
    public string $sort = CapaBoardFilters::SORT_RECENT;

    #[LiveProp(writable: true)]
    public int $page = 1;

    public function __construct(
        private readonly CapaBoardViewModelFactory $factory,
        private readonly Security $security,
    ) {
    }

    public function getViewModel(): CapaBoardViewModel
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Utilisateur requis pour le board CAPA.');
        }

        return $this->factory->build(
            $user,
            CapaBoardFilters::fromLiveProps(
                $this->search,
                $this->status,
                $this->capaType,
                $this->criticality,
                $this->overdueOnly,
                $this->dueFrom,
                $this->dueTo,
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
        $this->capaType = '';
        $this->criticality = '';
        $this->overdueOnly = false;
        $this->dueFrom = '';
        $this->dueTo = '';
        $this->sort = CapaBoardFilters::SORT_RECENT;
        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(int $targetPage): void
    {
        $this->page = max(1, $targetPage);
    }
}
