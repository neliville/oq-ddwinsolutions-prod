<?php

namespace App\Controller\Admin;

use App\Application\Analytics\TrackingEventType;
use App\Repository\AdminLogRepository;
use App\Repository\BlogPostRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\AnalyticsRepository;
use App\Repository\LeadRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageViewRepository;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Repository\TrackingEventRepository;
use App\Repository\UserRepository;
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
        private readonly TrackingEventRepository $trackingEventRepository,
        private readonly UserRepository $userRepository,
        private readonly CAPAActionRepository $capaActionRepository,
        private readonly AuditRepository $auditRepository,
        private readonly RiskMatrixEntryRepository $riskMatrixEntryRepository,
        private readonly AnalyticsRepository $analyticsRepository,
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
        $last7DaysStart = $todayStart->modify('-7 days');

        $trackingUsers7d = $this->trackingEventRepository->countDistinctUsersBetween($last7DaysStart, $now);
        $registrations7d = $this->userRepository->countCreatedBetween($last7DaysStart, $now);
        $loginReturns7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::LOGIN_RETURN, $last7DaysStart, $now);
        $dashboardOpens7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::DASHBOARD_OPENED, $last7DaysStart, $now);
        $exports7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::EXPORT_TRIGGERED, $last7DaysStart, $now);
        $capaEvents7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::CAPA_CREATED, $last7DaysStart, $now);
        $auditEvents7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::AUDIT_CREATED, $last7DaysStart, $now);
        $riskEvents7d = $this->trackingEventRepository->countByTypeBetween(TrackingEventType::RISK_CREATED, $last7DaysStart, $now);
        $capaEntities7d = $this->capaActionRepository->countCreatedBetween($last7DaysStart, $now);
        $auditEntities7d = $this->auditRepository->countCreatedBetween($last7DaysStart, $now);
        $riskEntities7d = $this->riskMatrixEntryRepository->countCreatedBetween($last7DaysStart, $now);
        $trackingByType7d = $this->trackingEventRepository->countGroupedByTypeBetween($last7DaysStart, $now);
        $topToolsOpened7d = $this->trackingEventRepository->countTopToolsOpenedBetween($last7DaysStart, $now, 8);
        $globalToolCreations = $this->analyticsRepository->getGlobalCreationCountsByTool();
        $recentTrackingEvents = $this->trackingEventRepository->findRecent(15);

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
            'trackingUsers7d' => $trackingUsers7d,
            'registrations7d' => $registrations7d,
            'loginReturns7d' => $loginReturns7d,
            'dashboardOpens7d' => $dashboardOpens7d,
            'exports7d' => $exports7d,
            'capaEvents7d' => $capaEvents7d,
            'auditEvents7d' => $auditEvents7d,
            'riskEvents7d' => $riskEvents7d,
            'capaEntities7d' => $capaEntities7d,
            'auditEntities7d' => $auditEntities7d,
            'riskEntities7d' => $riskEntities7d,
            'trackingByType7d' => $trackingByType7d,
            'topToolsOpened7d' => $topToolsOpened7d,
            'globalToolCreations' => $globalToolCreations,
            'recentTrackingEvents' => $recentTrackingEvents,
        ]);
    }
}
