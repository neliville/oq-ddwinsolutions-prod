<?php

namespace App\Controller\Admin;

use App\Repository\AdminLogRepository;
use App\Repository\BlogPostRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\LeadRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageViewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ContactMessageRepository $contactMessageRepository,
        private readonly NewsletterSubscriberRepository $newsletterSubscriberRepository,
        private readonly PageViewRepository $pageViewRepository,
        private readonly BlogPostRepository $blogPostRepository,
        private readonly AdminLogRepository $adminLogRepository,
        private readonly LeadRepository $leadRepository,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard_index')]
    public function index(): Response
    {
        $now = new \DateTimeImmutable();
        $todayStart = $now->setTime(0, 0, 0);
        $yesterdayStart = $todayStart->modify('-1 day');
        $lastWeekStart = $todayStart->modify('-7 days');
        $lastMonthStart = $todayStart->modify('-30 days');

        // Statistiques des messages de contact
        $unreadMessagesCount = $this->contactMessageRepository->countUnread();
        $messagesToday = $this->contactMessageRepository->countByPeriod($todayStart, $now);
        $messagesLastWeek = $this->contactMessageRepository->countByPeriod($lastWeekStart, $now);
        $messagesLastMonth = $this->contactMessageRepository->countByPeriod($lastMonthStart, $now);

        // Statistiques de la newsletter
        $activeSubscribersCount = $this->newsletterSubscriberRepository->countActive();
        $newSubscribersToday = $this->newsletterSubscriberRepository->countNewSubscribers($todayStart);
        $newSubscribersLastWeek = $this->newsletterSubscriberRepository->countNewSubscribers($lastWeekStart);
        $newSubscribersLastMonth = $this->newsletterSubscriberRepository->countNewSubscribers($lastMonthStart);

        // Statistiques des visites
        $visitsToday = $this->pageViewRepository->countByPeriod($todayStart, $now);
        $visitsLastWeek = $this->pageViewRepository->countByPeriod($lastWeekStart, $now);
        $visitsLastMonth = $this->pageViewRepository->countByPeriod($lastMonthStart, $now);
        $mostVisitedPages = $this->pageViewRepository->findMostVisitedPages(10);

        // Articles les plus vus
        $mostViewedPosts = $this->blogPostRepository->findMostViewed(10);

        // Activité récente
        $recentActivity = $this->adminLogRepository->findRecent(10);

        // Statistiques des leads (utilisation des outils)
        $leadsTotal = $this->leadRepository->count([]);
        $leadsToday = $this->leadRepository->countByPeriod($todayStart, $now);
        $leadsLastWeek = $this->leadRepository->countByPeriod($lastWeekStart, $now);
        $recentLeads = $this->leadRepository->findRecent(5);

        return $this->render('admin/dashboard/index.html.twig', [
            'unreadMessagesCount' => $unreadMessagesCount,
            'messagesToday' => $messagesToday,
            'messagesLastWeek' => $messagesLastWeek,
            'messagesLastMonth' => $messagesLastMonth,
            'activeSubscribersCount' => $activeSubscribersCount,
            'newSubscribersToday' => $newSubscribersToday,
            'newSubscribersLastWeek' => $newSubscribersLastWeek,
            'newSubscribersLastMonth' => $newSubscribersLastMonth,
            'visitsToday' => $visitsToday,
            'visitsLastWeek' => $visitsLastWeek,
            'visitsLastMonth' => $visitsLastMonth,
            'mostVisitedPages' => $mostVisitedPages,
            'mostViewedPosts' => $mostViewedPosts,
            'recentActivity' => $recentActivity,
            'leadsTotal' => $leadsTotal,
            'leadsToday' => $leadsToday,
            'leadsLastWeek' => $leadsLastWeek,
            'recentLeads' => $recentLeads,
        ]);
    }
}
