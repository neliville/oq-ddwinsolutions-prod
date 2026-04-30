<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnalyticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard', name: 'app_dashboard_')]
#[IsGranted('ROLE_USER')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepository,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $session = $request->getSession();
        $showOnboarding = (bool) $session->remove('_onboarding');

        $toolData = $this->analyticsRepository->getUserToolCounts($user->getId());

        return $this->render('dashboard/index.html.twig', array_merge($toolData, [
            'showOnboarding' => $showOnboarding,
        ]));
    }
}
