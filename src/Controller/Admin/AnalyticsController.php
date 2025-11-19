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
    public function index(): Response
    {
        return $this->render('admin/analytics/index.html.twig');
    }

    #[Route('/traffic', name: 'traffic', methods: ['GET'])]
    public function traffic(Request $request): Response
    {
        [$period, $start, $end, $now] = $this->resolvePeriod($request, 'today');

        // Calculer les dates de référence (créer de nouvelles instances pour éviter les modifications)
        $todayStart = (clone $now)->setTime(0, 0, 0);
        $todayEnd = (clone $now)->setTime(23, 59, 59);
        
        // Hier (jour précédent)
        $yesterday = (clone $now)->modify('-1 day');
        $yesterdayStart = $yesterday->setTime(0, 0, 0);
        $yesterdayEnd = (clone $yesterday)->setTime(23, 59, 59);
        
        // Semaine précédente (même jour il y a 7 jours)
        $lastWeekDay = (clone $now)->modify('-7 days');
        $lastWeekStart = $lastWeekDay->setTime(0, 0, 0);
        $lastWeekEnd = (clone $lastWeekDay)->setTime(23, 59, 59);
        
        // Mois précédent (même jour il y a 30 jours)
        $lastMonthDay = (clone $now)->modify('-30 days');
        $lastMonthStart = $lastMonthDay->setTime(0, 0, 0);
        $lastMonthEnd = (clone $lastMonthDay)->setTime(23, 59, 59);
        
        // Visites pour la période sélectionnée
        $totalVisits = $this->pageViewRepository->countByPeriod($start, $end);
        
        // Visites aujourd'hui
        $todayVisits = $this->pageViewRepository->countByPeriod($todayStart, $todayEnd);
        
        // Visites hier
        $yesterdayVisits = $this->pageViewRepository->countByPeriod($yesterdayStart, $yesterdayEnd);
        
        // Visites semaine précédente (même jour de la semaine il y a 7 jours)
        $lastWeekVisits = $this->pageViewRepository->countByPeriod($lastWeekStart, $lastWeekEnd);
        
        // Visites mois précédent (même jour il y a 30 jours)
        $lastMonthVisits = $this->pageViewRepository->countByPeriod($lastMonthStart, $lastMonthEnd);
        
        // Utilisateurs connectés et anonymes pour la période sélectionnée
        $authenticatedVisits = $this->pageViewRepository->countByUserTypeAndPeriod(true, $start, $end);
        $anonymousVisits = $this->pageViewRepository->countByUserTypeAndPeriod(false, $start, $end);

        $visitsByDay = $this->normalizeDailyData($this->pageViewRepository->findVisitsByDay($start, $end));
        
        // Pour les visites mensuelles, utiliser les 12 derniers mois
        $yearStart = (clone $now)->modify('-365 days')->setTime(0, 0, 0);
        $visitsByMonth = $this->normalizeMonthlyData(
            $this->pageViewRepository->findVisitsByMonth($yearStart, $now)
        );

        $mostVisitedPages = $this->pageViewRepository->findMostVisitedPages(5);
        $topCountries = $this->pageViewRepository->findTopCountries(5);
        $topDevices = $this->pageViewRepository->findTopDevices(5);

        $visitsDailyChart = [
            'labels' => array_map(
                static fn (array $row): string => $row['date']?->format('d/m') ?? '',
                $visitsByDay
            ),
            'data' => array_map(static fn (array $row): int => $row['count'], $visitsByDay),
        ];

        $visitsMonthlyChart = [
            'labels' => array_map(static fn (array $row): string => $row['period'], $visitsByMonth),
            'data' => array_map(static fn (array $row): int => $row['count'], $visitsByMonth),
        ];

        $deviceChart = [
            'labels' => array_map(static fn (array $row): string => $row['device'] ?? 'Inconnu', $topDevices),
            'data' => array_map(static fn (array $row): int => (int) $row['visitCount'], $topDevices),
        ];

        $countryChart = [
            'labels' => array_map(static fn (array $row): string => $row['country'] ?? 'Inconnu', $topCountries),
            'data' => array_map(static fn (array $row): int => (int) $row['visitCount'], $topCountries),
        ];

        // Récupérer les logs de navigation récents avec pagination
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $filters = [
            'from' => $start,
            'to' => $end,
        ];
        $logsResult = $this->pageViewRepository->searchWithFilters($filters, $page, $limit);
        $navigationLogs = $logsResult['data'];
        $totalLogs = $logsResult['total'];
        $totalPages = (int) ceil($totalLogs / $limit);

        return $this->render('admin/analytics/traffic.html.twig', [
            'period' => $period,
            'totalVisits' => $totalVisits,
            'todayVisits' => $todayVisits,
            'yesterdayVisits' => $yesterdayVisits,
            'lastWeekVisits' => $lastWeekVisits,
            'lastMonthVisits' => $lastMonthVisits,
            'authenticatedVisits' => $authenticatedVisits,
            'anonymousVisits' => $anonymousVisits,
            'mostVisitedPages' => $mostVisitedPages,
            'visitsDailyChart' => $visitsDailyChart,
            'visitsMonthlyChart' => $visitsMonthlyChart,
            'deviceChart' => $deviceChart,
            'countryChart' => $countryChart,
            'startDate' => $start,
            'endDate' => $end,
            'navigationLogs' => $navigationLogs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalLogs' => $totalLogs,
        ]);
    }

    #[Route('/sharing', name: 'sharing', methods: ['GET'])]
    public function sharing(Request $request): Response
    {
        [$period, $start, $end, $now] = $this->resolvePeriod($request, 'month');

        $shareTotals = [
            'totalShares' => $this->shareRepository->countAll(),
            'activeShares' => $this->shareRepository->countActive($now),
            'sharesThisPeriod' => $this->shareRepository->countByPeriod($start, $end),
            'totalShareVisits' => $this->shareVisitRepository->countAll(),
            'shareVisitsThisPeriod' => $this->shareVisitRepository->countByPeriod($start, $end),
        ];

        $sharesByDay = $this->shareRepository->findSharesByDay($start, $end);
        $shareVisitsByDay = $this->shareVisitRepository->findVisitsByDay($start, $end);

        $shareChart = [
            'labels' => array_map(
                fn (array $row): string => $this->formatDateLabel($row['share_date'] ?? ''),
                $sharesByDay
            ),
            'data' => array_map(static fn (array $row): int => (int) $row['share_count'], $sharesByDay),
        ];

        $shareVisitChart = [
            'labels' => array_map(
                fn (array $row): string => $this->formatDateLabel($row['visit_date'] ?? ''),
                $shareVisitsByDay
            ),
            'data' => array_map(static fn (array $row): int => (int) $row['visit_count'], $shareVisitsByDay),
        ];

        $topSharers = $this->shareRepository->findTopSharers(5);
        $topSharedAnalyses = $this->shareVisitRepository->findTopSharedAnalyses(5);
        $recentShares = $this->shareRepository->findRecentShares(10);
        $recentShareVisits = $this->shareVisitRepository->findRecentVisits(10);

        return $this->render('admin/analytics/sharing.html.twig', [
            'period' => $period,
            'shareTotals' => $shareTotals,
            'shareChart' => $shareChart,
            'shareVisitChart' => $shareVisitChart,
            'topSharers' => $topSharers,
            'topSharedAnalyses' => $topSharedAnalyses,
            'recentShares' => $recentShares,
            'recentShareVisits' => $recentShareVisits,
            'startDate' => $start,
            'endDate' => $end,
        ]);
    }

    /**
     * @return array{0:string,1:\DateTimeImmutable,2:\DateTimeImmutable,3:\DateTimeImmutable}
     */
    private function resolvePeriod(Request $request, string $default = 'month'): array
    {
        $period = $request->query->get('period', $default);
        $now = new \DateTimeImmutable();
        $end = $now;

        $start = match ($period) {
            'today' => $now->setTime(0, 0, 0),
            'week' => $now->modify('-7 days')->setTime(0, 0, 0),
            'month' => $now->modify('-30 days')->setTime(0, 0, 0),
            'year' => $now->modify('-365 days')->setTime(0, 0, 0),
            default => $now->modify('-30 days')->setTime(0, 0, 0),
        };

        return [$period, $start, $end, $now];
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     * @return array<int, array{date:\DateTimeImmutable|null, count:int}>
     */
    private function normalizeDailyData(array $rows): array
    {
        return array_map(static function (array $row): array {
            $dateString = $row['visit_date'] ?? $row['date'] ?? null;
            $count = (int) ($row['visit_count'] ?? $row['count'] ?? 0);

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
        }, $rows);
    }

    /**
     * @param array<int, array<string,mixed>> $rows
     * @return array<int, array{period:string, count:int}>
     */
    private function normalizeMonthlyData(array $rows): array
    {
        return array_map(static function (array $row): array {
            $monthString = $row['visit_month'] ?? $row['period'] ?? '';
            $count = (int) ($row['visit_count'] ?? $row['count'] ?? 0);

            return [
                'period' => $monthString,
                'count' => $count,
            ];
        }, $rows);
    }

    private function formatDateLabel(?string $value): string
    {
        if (!$value) {
            return '';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value) ?: null;
        if (!$date) {
            try {
                $date = new \DateTimeImmutable($value);
            } catch (\Exception) {
                return $value;
            }
        }

        return $date->format('d/m');
    }
}
