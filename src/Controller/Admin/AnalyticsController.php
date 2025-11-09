<?php

namespace App\Controller\Admin;

use App\Repository\IshikawaShareRepository;
use App\Repository\IshikawaShareVisitRepository;
use App\Repository\PageViewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/analytics', name: 'app_admin_analytics_')]
#[IsGranted('ROLE_ADMIN')]
final class AnalyticsController extends AbstractController
{
    public function __construct(
        private readonly PageViewRepository $pageViewRepository,
        private readonly IshikawaShareRepository $shareRepository,
        private readonly IshikawaShareVisitRepository $shareVisitRepository,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Période par défaut : 30 derniers jours
        $period = $request->query->get('period', '30days');
        
        $now = new \DateTimeImmutable();
        $start = match ($period) {
            'today' => $now->setTime(0, 0, 0),
            'week' => $now->modify('-7 days')->setTime(0, 0, 0),
            'month' => $now->modify('-30 days')->setTime(0, 0, 0),
            'year' => $now->modify('-365 days')->setTime(0, 0, 0),
            default => $now->modify('-30 days')->setTime(0, 0, 0),
        };

        // Statistiques globales
        $totalVisits = $this->pageViewRepository->countByPeriod($start, $now);
        $todayVisits = $this->pageViewRepository->countByPeriod(
            $now->setTime(0, 0, 0),
            $now
        );
        $authenticatedVisits = $this->pageViewRepository->countByUserType(true);
        $anonymousVisits = $this->pageViewRepository->countByUserType(false);

        // Pages les plus visitées
        $mostVisitedPages = $this->pageViewRepository->findMostVisitedPages(10);

        // Top référents
        $topReferers = $this->pageViewRepository->findTopReferers(10);

        // Données géographiques
        $topCountries = $this->pageViewRepository->findTopCountries(10);
        $topCities = $this->pageViewRepository->findTopCities(10);

        // Appareils
        $topDevices = $this->pageViewRepository->findTopDevices(10);

        // Tendances
        $rawVisitsByDay = $this->pageViewRepository->findVisitsByDay($start, $now);
        $rawVisitsByMonth = $this->pageViewRepository->findVisitsByMonth(
            $now->modify('-365 days')->setTime(0, 0, 0),
            $now
        );

        $visitsByDay = array_map(static function (array $row): array {
            $dateString = $row['visit_date'] ?? $row['visitDate'] ?? null;
            $count = (int) ($row['visit_count'] ?? $row['visitCount'] ?? 0);

            $date = null;
            if ($dateString) {
                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateString) ?: null;
                if (!$date) {
                    $date = new \DateTimeImmutable($dateString);
                }
            }

            return [
                'date' => $date,
                'count' => $count,
            ];
        }, $rawVisitsByDay);

        $visitsByMonth = array_map(static function (array $row): array {
            $monthString = $row['visit_month'] ?? $row['visitMonth'] ?? '';
            $count = (int) ($row['visit_count'] ?? $row['visitCount'] ?? 0);

            return [
                'period' => $monthString,
                'count' => $count,
            ];
        }, $rawVisitsByMonth);

        // Statistiques par période (hier, semaine dernière, mois dernier)
        $yesterdayStart = $now->modify('-1 day')->setTime(0, 0, 0);
        $yesterdayEnd = $now->modify('-1 day')->setTime(23, 59, 59);
        $yesterdayVisits = $this->pageViewRepository->countByPeriod($yesterdayStart, $yesterdayEnd);

        $lastWeekStart = $now->modify('-14 days')->setTime(0, 0, 0);
        $lastWeekEnd = $now->modify('-8 days')->setTime(23, 59, 59);
        $lastWeekVisits = $this->pageViewRepository->countByPeriod($lastWeekStart, $lastWeekEnd);

        $lastMonthStart = $now->modify('-60 days')->setTime(0, 0, 0);
        $lastMonthEnd = $now->modify('-31 days')->setTime(23, 59, 59);
        $lastMonthVisits = $this->pageViewRepository->countByPeriod($lastMonthStart, $lastMonthEnd);

        // Tableau détaillé des visites
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(10, min(100, (int) $request->query->get('limit', 25)));

        $fromInput = $request->query->get('from');
        $toInput = $request->query->get('to');

        $fromDate = $fromInput ? (\DateTimeImmutable::createFromFormat('Y-m-d', $fromInput) ?: null) : null;
        $toDate = $toInput ? (\DateTimeImmutable::createFromFormat('Y-m-d', $toInput) ?: null) : null;

        if ($fromDate instanceof \DateTimeImmutable) {
            $fromDate = $fromDate->setTime(0, 0, 0);
        }

        if ($toDate instanceof \DateTimeImmutable) {
            $toDate = $toDate->setTime(23, 59, 59);
        }

        $filters = [
            'from' => $fromDate ?? $start,
            'to' => $toDate ?? $now,
            'url' => $request->query->get('url'),
            'referer' => $request->query->get('referer'),
            'userEmail' => $request->query->get('userEmail'),
            'method' => $request->query->get('method'),
            'ipAddress' => $request->query->get('ipAddress'),
            'sessionId' => $request->query->get('sessionId'),
            'country' => $request->query->get('country'),
            'city' => $request->query->get('city'),
            'type' => $request->query->get('type'),
        ];

        $searchResult = $this->pageViewRepository->searchWithFilters($filters, $page, $limit);

        $pageViews = $searchResult['data'];
        $totalPageViews = $searchResult['total'];
        $totalPages = (int) max(1, ceil($totalPageViews / $limit));

        $filtersForView = [
            'from' => ($fromDate ?? $start)->format('Y-m-d'),
            'to' => ($toDate ?? $now)->format('Y-m-d'),
            'url' => $request->query->get('url', ''),
            'referer' => $request->query->get('referer', ''),
            'userEmail' => $request->query->get('userEmail', ''),
            'method' => $request->query->get('method', ''),
            'ipAddress' => $request->query->get('ipAddress', ''),
            'sessionId' => $request->query->get('sessionId', ''),
            'country' => $request->query->get('country', ''),
            'city' => $request->query->get('city', ''),
            'type' => $request->query->get('type', ''),
        ];
 
        $sharePeriodStart = (clone $now)->modify('-30 days')->setTime(0, 0, 0);
        $shareTotals = [
            'totalShares' => $this->shareRepository->countAll(),
            'activeShares' => $this->shareRepository->countActive($now),
            'sharesThisMonth' => $this->shareRepository->countByPeriod($sharePeriodStart, $now),
            'totalShareVisits' => $this->shareVisitRepository->countAll(),
            'shareVisitsThisMonth' => $this->shareVisitRepository->countByPeriod($sharePeriodStart, $now),
        ];

        $topSharers = $this->shareRepository->findTopSharers(5);
        $topSharedAnalyses = $this->shareVisitRepository->findTopSharedAnalyses(5);
        $recentShares = $this->shareRepository->findRecentShares(10);
        $recentShareVisits = $this->shareVisitRepository->findRecentVisits(10);

        return $this->render('admin/analytics/index.html.twig', [
            'period' => $period,
            'totalVisits' => $totalVisits,
            'todayVisits' => $todayVisits,
            'yesterdayVisits' => $yesterdayVisits,
            'lastWeekVisits' => $lastWeekVisits,
            'lastMonthVisits' => $lastMonthVisits,
            'authenticatedVisits' => $authenticatedVisits,
            'anonymousVisits' => $anonymousVisits,
            'mostVisitedPages' => $mostVisitedPages,
            'topReferers' => $topReferers,
            'topCountries' => $topCountries,
            'topCities' => $topCities,
            'topDevices' => $topDevices,
            'visitsByDay' => $visitsByDay,
            'visitsByMonth' => $visitsByMonth,
            'startDate' => $start,
            'endDate' => $now,
            'pageViews' => $pageViews,
            'totalPageViews' => $totalPageViews,
            'currentPage' => $page,
            'pageSize' => $limit,
            'totalPages' => $totalPages,
            'filters' => $filtersForView,
            'shareTotals' => $shareTotals,
            'topSharers' => $topSharers,
            'topSharedAnalyses' => $topSharedAnalyses,
            'recentShares' => $recentShares,
            'recentShareVisits' => $recentShareVisits,
        ]);
    }
}
