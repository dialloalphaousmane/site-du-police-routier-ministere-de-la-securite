<?php

namespace App\Controller\API;

use App\Repository\UserRepository;
use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use App\Repository\AmendeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/stats')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class StatsController extends AbstractController
{
    private $userRepository;
    private $controleRepository;
    private $infractionRepository;
    private $regionRepository;
    private $brigadeRepository;
    private $amendeRepository;
    private $entityManager;

    public function __construct(
        UserRepository $userRepository,
        ControleRepository $controleRepository,
        InfractionRepository $infractionRepository,
        RegionRepository $regionRepository,
        BrigadeRepository $brigadeRepository,
        AmendeRepository $amendeRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->controleRepository = $controleRepository;
        $this->infractionRepository = $infractionRepository;
        $this->regionRepository = $regionRepository;
        $this->brigadeRepository = $brigadeRepository;
        $this->amendeRepository = $amendeRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/users', name: 'api_stats_users', methods: ['GET'])]
    public function getUsersStats(): JsonResponse
    {
        $totalUsers = $this->userRepository->count(['isActive' => true]);
        $activeUsers = $this->userRepository->count(['isActive' => true]);
        $inactiveUsers = $this->userRepository->count(['isActive' => false]);

        // Statistiques par rôle
        $roles = ['ROLE_ADMIN', 'ROLE_DIRECTION_GENERALE', 'ROLE_DIRECTION_REGIONALE', 'ROLE_CHEF_BRIGADE', 'ROLE_AGENT'];
        $usersByRole = [];

        foreach ($roles as $role) {
            $qb = $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.isActive = :active')
                ->andWhere('u.roles LIKE :role')
                ->setParameter('active', true)
                ->setParameter('role', '%"' . $role . '"%');
            
            $usersByRole[$role] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        return new JsonResponse([
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'users_by_role' => $usersByRole,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/controls', name: 'api_stats_controls', methods: ['GET'])]
    public function getControlsStats(): JsonResponse
    {
        $today = new \DateTimeImmutable('today');
        $yesterday = new \DateTimeImmutable('yesterday');
        $thisMonth = new \DateTimeImmutable('first day of this month');
        $lastMonth = new \DateTimeImmutable('first day of last month');

        // Statistiques du jour
        $todayControls = $this->controleRepository->count(['date' => ['>=' => $today]]);
        $yesterdayControls = $this->controleRepository->count(['date' => ['>=' => $yesterday, '<' => $today]]);

        // Statistiques du mois
        $thisMonthControls = $this->controleRepository->count(['date' => ['>=' => $thisMonth]]);
        $lastMonthControls = $this->controleRepository->count(['date' => ['>=' => $lastMonth, '<' => $thisMonth]]);

        // Total des contrôles
        $totalControls = $this->controleRepository->count([]);

        // Évolution sur 7 jours
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $last7DaysControls = $this->controleRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)', 'DATE(c.date) as date')
            ->where('c.date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->groupBy('DATE(c.date)')
            ->orderBy('DATE(c.date)', 'ASC')
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'today_controls' => $todayControls,
            'yesterday_controls' => $yesterdayControls,
            'this_month_controls' => $thisMonthControls,
            'last_month_controls' => $lastMonthControls,
            'total_controls' => $totalControls,
            'evolution_7_days' => $last7DaysControls,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/infractions', name: 'api_stats_infractions', methods: ['GET'])]
    public function getInfractionsStats(): JsonResponse
    {
        $today = new \DateTimeImmutable('today');
        $thisMonth = new \DateTimeImmutable('first day of this month');

        // Statistiques du jour
        $todayInfractions = $this->infractionRepository->count(['date' => ['>=' => $today]]);

        // Statistiques du mois
        $thisMonthInfractions = $this->infractionRepository->count(['date' => ['>=' => $thisMonth]]);

        // Total des infractions
        $totalInfractions = $this->infractionRepository->count([]);

        // Répartition par type
        $infractionsByType = $this->infractionRepository->createQueryBuilder('i')
            ->select('COUNT(i.id) as count', 'i.type')
            ->groupBy('i.type')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'today_infractions' => $todayInfractions,
            'this_month_infractions' => $thisMonthInfractions,
            'total_infractions' => $totalInfractions,
            'infractions_by_type' => $infractionsByType,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/revenue', name: 'api_stats_revenue', methods: ['GET'])]
    public function getRevenueStats(): JsonResponse
    {
        $today = new \DateTimeImmutable('today');
        $thisMonth = new \DateTimeImmutable('first day of this month');

        // Calcul des revenus du jour
        $qbToday = $this->amendeRepository->createQueryBuilder('a')
            ->select('SUM(a.montant)')
            ->where('a.datePaiement >= :date')
            ->setParameter('date', $today);
        
        $todayRevenue = $qbToday->getQuery()->getSingleScalarResult() ?? 0;

        // Calcul des revenus du mois
        $qbMonth = $this->amendeRepository->createQueryBuilder('a')
            ->select('SUM(a.montant)')
            ->where('a.datePaiement >= :date')
            ->setParameter('date', $thisMonth);
        
        $thisMonthRevenue = $qbMonth->getQuery()->getSingleScalarResult() ?? 0;

        // Total des revenus
        $qbTotal = $this->amendeRepository->createQueryBuilder('a')
            ->select('SUM(a.montant)');
        
        $totalRevenue = $qbTotal->getQuery()->getSingleScalarResult() ?? 0;

        // Revenus par mois (6 derniers mois)
        $sixMonthsAgo = new \DateTimeImmutable('-6 months');
        $revenueByMonth = $this->amendeRepository->createQueryBuilder('a')
            ->select('SUM(a.montant) as revenue', 'DATE_FORMAT(a.datePaiement, \'%Y-%m\') as month')
            ->where('a.datePaiement >= :date')
            ->setParameter('date', $sixMonthsAgo)
            ->groupBy('DATE_FORMAT(a.datePaiement, \'%Y-%m\')')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'today_revenue' => (int) $todayRevenue,
            'this_month_revenue' => (int) $thisMonthRevenue,
            'total_revenue' => (int) $totalRevenue,
            'revenue_by_month' => $revenueByMonth,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/regions', name: 'api_stats_regions', methods: ['GET'])]
    public function getRegionsStats(): JsonResponse
    {
        $regions = $this->regionRepository->findAll();
        $regionsStats = [];

        foreach ($regions as $region) {
            $qbControls = $this->controleRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->innerJoin('c.brigade', 'b')
                ->where('b.region = :region')
                ->setParameter('region', $region);
            
            $qbInfractions = $this->infractionRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->innerJoin('i.controle', 'c')
                ->innerJoin('c.brigade', 'b')
                ->where('b.region = :region')
                ->setParameter('region', $region);

            $qbRevenue = $this->amendeRepository->createQueryBuilder('a')
                ->select('SUM(a.montant)')
                ->innerJoin('a.infraction', 'i')
                ->innerJoin('i.controle', 'c')
                ->innerJoin('c.brigade', 'b')
                ->where('b.region = :region')
                ->setParameter('region', $region);

            $regionsStats[] = [
                'id' => $region->getId(),
                'name' => $region->getLibelle(),
                'code' => $region->getCode(),
                'controls' => (int) $qbControls->getQuery()->getSingleScalarResult(),
                'infractions' => (int) $qbInfractions->getQuery()->getSingleScalarResult(),
                'revenue' => (int) ($qbRevenue->getQuery()->getSingleScalarResult() ?? 0),
                'active' => $region->isActif()
            ];
        }

        return new JsonResponse([
            'regions' => $regionsStats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/brigades', name: 'api_stats_brigades', methods: ['GET'])]
    public function getBrigadesStats(): JsonResponse
    {
        $brigades = $this->brigadeRepository->findAll();
        $brigadesStats = [];

        foreach ($brigades as $brigade) {
            $qbControls = $this->controleRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.brigade = :brigade')
                ->setParameter('brigade', $brigade);
            
            $qbInfractions = $this->infractionRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->innerJoin('i.controle', 'c')
                ->where('c.brigade = :brigade')
                ->setParameter('brigade', $brigade);

            $qbRevenue = $this->amendeRepository->createQueryBuilder('a')
                ->select('SUM(a.montant)')
                ->innerJoin('a.infraction', 'i')
                ->innerJoin('i.controle', 'c')
                ->where('c.brigade = :brigade')
                ->setParameter('brigade', $brigade);

            $qbAgents = $this->userRepository->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.brigade = :brigade')
                ->andWhere('u.isActive = :active')
                ->setParameter('brigade', $brigade)
                ->setParameter('active', true);

            $brigadesStats[] = [
                'id' => $brigade->getId(),
                'name' => $brigade->getLibelle(),
                'code' => $brigade->getCode(),
                'region' => $brigade->getRegion() ? $brigade->getRegion()->getLibelle() : null,
                'controls' => (int) $qbControls->getQuery()->getSingleScalarResult(),
                'infractions' => (int) $qbInfractions->getQuery()->getSingleScalarResult(),
                'revenue' => (int) ($qbRevenue->getQuery()->getSingleScalarResult() ?? 0),
                'agents' => (int) $qbAgents->getQuery()->getSingleScalarResult(),
                'active' => $brigade->isActif()
            ];
        }

        return new JsonResponse([
            'brigades' => $brigadesStats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    #[Route('/controls-evolution', name: 'api_stats_controls_evolution', methods: ['GET'])]
    public function getControlsEvolution(Request $request): JsonResponse
    {
        $period = $request->query->get('period', '7days');
        $connection = $this->entityManager->getConnection();
        
        switch ($period) {
            case '7days':
                $sql = "
                    SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM controle
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ";
                break;
            case '30days':
                $sql = "
                    SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM controle
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ";
                break;
            case '3months':
                $sql = "
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as date, COUNT(*) as count
                    FROM controle
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY date
                ";
                break;
            case '1year':
                $sql = "
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as date, COUNT(*) as count
                    FROM controle
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR)
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY date
                ";
                break;
            default:
                $sql = "
                    SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM controle
                    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date
                ";
        }

        $stmt = $connection->executeQuery($sql);
        $results = $stmt->fetchAllAssociative();

        $labels = [];
        $data = [];

        foreach ($results as $result) {
            $labels[] = $result['date'];
            $data[] = $result['count'];
        }

        return new JsonResponse([
            'labels' => $labels,
            'data' => $data,
            'period' => $period
        ]);
    }

    #[Route('/dashboard', name: 'api_stats_dashboard', methods: ['GET'])]
    public function getDashboardStats(): JsonResponse
    {
        // Récupération de toutes les statistiques pour le dashboard
        $usersStats = json_decode($this->getUsersStats()->getContent(), true);
        $controlsStats = json_decode($this->getControlsStats()->getContent(), true);
        $infractionsStats = json_decode($this->getInfractionsStats()->getContent(), true);
        $revenueStats = json_decode($this->getRevenueStats()->getContent(), true);

        return new JsonResponse([
            'users' => $usersStats,
            'controls' => $controlsStats,
            'infractions' => $infractionsStats,
            'revenue' => $revenueStats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
