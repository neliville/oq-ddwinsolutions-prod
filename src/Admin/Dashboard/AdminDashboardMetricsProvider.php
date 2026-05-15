<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use App\Application\Analytics\TrackingEventType;
use App\Repository\AdminLogRepository;
use App\Repository\AnalyticsRepository;
use App\Repository\BlogPostRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\LeadRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageViewRepository;
use App\Repository\Qse\AuditRepository;
use App\Repository\Qse\CAPAActionRepository;
use App\Repository\Qse\RiskMatrixEntryRepository;
use App\Repository\TrackingEventRepository;
use App\Repository\UserRepository;

/**
 * Agrégations du tableau de bord admin — hors contrôleur pour tests et réutilisation.
 */
final class AdminDashboardMetricsProvider
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
        private readonly PlatformIntegrationSummaryProvider $platformIntegrationSummaryProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewModel(): array
    {
        $now = new \DateTimeImmutable();
        $todayStart = $now->setTime(0, 0, 0);
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

        $unreadMessagesCount = $this->contactMessageRepository->countUnread();
        $messagesToday = $this->contactMessageRepository->countByPeriod($todayStart, $now);
        $messagesLastWeek = $this->contactMessageRepository->countByPeriod($lastWeekStart, $now);
        $messagesLastMonth = $this->contactMessageRepository->countByPeriod($lastMonthStart, $now);

        $activeSubscribersCount = $this->newsletterSubscriberRepository->countActive();
        $newSubscribersToday = $this->newsletterSubscriberRepository->countNewSubscribers($todayStart);
        $newSubscribersLastWeek = $this->newsletterSubscriberRepository->countNewSubscribers($lastWeekStart);
        $newSubscribersLastMonth = $this->newsletterSubscriberRepository->countNewSubscribers($lastMonthStart);

        $visitsToday = $this->pageViewRepository->countByPeriod($todayStart, $now);
        $visitsLastWeek = $this->pageViewRepository->countByPeriod($lastWeekStart, $now);
        $visitsLastMonth = $this->pageViewRepository->countByPeriod($lastMonthStart, $now);
        $mostVisitedPages = $this->pageViewRepository->findMostVisitedPages(10);

        $mostViewedPosts = $this->blogPostRepository->findMostViewed(10);

        $recentActivity = $this->adminLogRepository->findRecent(10);

        $leadsTotal = $this->leadRepository->count([]);
        $leadsToday = $this->leadRepository->countByPeriod($todayStart, $now);
        $leadsLastWeek = $this->leadRepository->countByPeriod($lastWeekStart, $now);
        $recentLeads = $this->leadRepository->findRecent(5);

        $blogPostsPublishedCount = (int) $this->blogPostRepository->countPublishedByCategory(null);

        $regChartLabels = [];
        $regChartValues = [];
        $visChartLabels = [];
        $visChartValues = [];
        for ($d = 6; $d >= 0; --$d) {
            $dayStart = $todayStart->modify('-'.$d.' days');
            $nextDayStart = $dayStart->modify('+1 day');
            $dayLabel = $dayStart->format('d/m');
            $regChartLabels[] = $dayLabel;
            $regChartValues[] = $this->userRepository->countCreatedBetween($dayStart, $nextDayStart);
            $visDayEnd = $dayStart->setTime(23, 59, 59);
            $visChartLabels[] = $dayLabel;
            $visChartValues[] = $this->pageViewRepository->countByPeriod($dayStart, $visDayEnd);
        }

        $adminDashboardCharts = [
            'registrations' => [
                'labels' => $regChartLabels,
                'values' => $regChartValues,
            ],
            'visits' => [
                'labels' => $visChartLabels,
                'values' => $visChartValues,
            ],
            'engagement' => [
                'labels' => [
                    'Utilisateurs actifs (TE)',
                    'Inscriptions',
                    'Retours connexion',
                    'Ouvertures cockpit',
                    'Exports déclenchés',
                ],
                'values' => [
                    $trackingUsers7d,
                    $registrations7d,
                    $loginReturns7d,
                    $dashboardOpens7d,
                    $exports7d,
                ],
            ],
        ];

        return [
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
            'platformIntegrationSummary' => $this->platformIntegrationSummaryProvider->summarize(),
            'blogPostsPublishedCount' => $blogPostsPublishedCount,
            'adminDashboardCharts' => $adminDashboardCharts,
        ];
    }
}
