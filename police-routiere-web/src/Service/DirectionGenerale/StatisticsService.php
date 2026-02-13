<?php

namespace App\Service\DirectionGenerale;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Region;
use App\Entity\Controle;
use App\Entity\Infraction;
use App\Entity\Amende;
use App\Entity\Rapport;

class StatisticsService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDashboardStats(): array
    {
        try {
            $regionRepository = $this->entityManager->getRepository(Region::class);
            $controleRepository = $this->entityManager->getRepository(Controle::class);
            $infractionRepository = $this->entityManager->getRepository(Infraction::class);
            $amendeRepository = $this->entityManager->getRepository(Amende::class);
            
            // Statistiques réelles depuis la base de données
            $totalRegions = $regionRepository->count(['actif' => true]);
            $totalControls = $controleRepository->count([]);
            $totalInfractions = $infractionRepository->count([]);
            
            // Contrôles du jour
            $todayControls = $controleRepository->createQueryBuilder('c')
                ->where('c.dateControle >= :today')
                ->setParameter('today', new \DateTimeImmutable('today'))
                ->select('COUNT(c.id)')
                ->getQuery()
                ->getSingleScalarResult();
            
            // Revenus du jour (amendes payées)
            $todayRevenue = $amendeRepository->createQueryBuilder('a')
                ->select('COALESCE(SUM(a.montantPaye), 0)')
                ->where('a.datePaiement >= :today')
                ->andWhere('a.statut = :statut')
                ->setParameter('today', new \DateTimeImmutable('today'))
                ->setParameter('statut', 'PAYEE')
                ->getQuery()
                ->getSingleScalarResult();
            
            // Agents actifs
            $activeAgents = $this->entityManager->createQueryBuilder()
                ->select('COUNT(a.id)')
                ->from('App\Entity\Agent', 'a')
                ->where('a.isActif = true')
                ->getQuery()
                ->getSingleScalarResult();
            
            return [
                'total_regions' => $totalRegions,
                'total_controls' => $totalControls,
                'total_infractions' => $totalInfractions,
                'today_controls' => $todayControls,
                'today_revenue' => (int) $todayRevenue,
                'active_agents' => $activeAgents,
                'compliance_rate' => $totalControls > 0 ? (($totalControls - $totalInfractions) / $totalControls) * 100 : 0
            ];
        } catch (\Exception $e) {
            return [
                'total_regions' => 0,
                'total_controls' => 0,
                'total_infractions' => 0,
                'today_controls' => 0,
                'today_revenue' => 0,
                'active_agents' => 0,
                'compliance_rate' => 0
            ];
        }
    }

    public function getControlsEvolution(): array
    {
        try {
            // Évolution des contrôles sur les 12 derniers mois
            $controlsByMonth = $this->entityManager->getRepository(Controle::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id) as count, SUBSTRING(c.dateControle, 1, 7) as month')
                ->where('c.dateControle >= :date')
                ->setParameter('date', new \DateTimeImmutable('-12 months'))
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->getQuery()
                ->getResult();

            $countsByMonth = [];
            foreach ($controlsByMonth as $row) {
                if (!isset($row['month'])) {
                    continue;
                }
                $countsByMonth[(string) $row['month']] = (int) ($row['count'] ?? 0);
            }

            $labels = [];
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthKey = (new \DateTimeImmutable("-$i months"))->format('Y-m');
                $labels[] = $monthKey;
                $data[] = $countsByMonth[$monthKey] ?? 0;
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'labels' => [],
                'data' => []
            ];
        }
    }

    public function getControlsByRegion(): array
    {
        try {
            $rows = $this->entityManager->getRepository(Controle::class)
                ->createQueryBuilder('c')
                ->select('COUNT(c.id) as count, r.libelle as region')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->groupBy('r.id')
                ->orderBy('count', 'DESC')
                ->getQuery()
                ->getResult();

            $labels = [];
            $data = [];
            foreach ($rows as $row) {
                $labels[] = (string) ($row['region'] ?? '');
                $data[] = (int) ($row['count'] ?? 0);
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'labels' => [],
                'data' => [],
            ];
        }
    }

    public function getInfractionsByType(): array
    {
        try {
            $infractionsByType = $this->entityManager->getRepository(Infraction::class)
                ->createQueryBuilder('i')
                ->select('COUNT(i.id) as count, i.libelle as type')
                ->groupBy('i.libelle')
                ->getQuery()
                ->getResult();
            
            $labels = [];
            $data = [];
            
            foreach ($infractionsByType as $typeData) {
                $labels[] = $typeData['type'];
                $data[] = $typeData['count'];
            }
            
            return [
                'labels' => $labels,
                'data' => $data
            ];
        } catch (\Exception $e) {
            // Données par défaut en cas d'erreur
            return [
                'labels' => ['Vitesse', 'Alcool', 'Ceinture', 'Téléphone', 'Autres'],
                'data' => [45, 23, 18, 12, 34]
            ];
        }
    }

    public function getMonthlyRevenue(): array
    {
        try {
            // Revenus mensuels (amendes payées) sur les 12 derniers mois
            $revenueByMonth = $this->entityManager->getRepository(Amende::class)
                ->createQueryBuilder('a')
                ->select('COALESCE(SUM(a.montantPaye), 0) as revenue, SUBSTRING(a.datePaiement, 1, 7) as month')
                ->where('a.datePaiement >= :date')
                ->andWhere('a.statut = :statut')
                ->setParameter('date', new \DateTimeImmutable('-12 months'))
                ->setParameter('statut', 'PAYEE')
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->getQuery()
                ->getResult();

            $revenueMap = [];
            foreach ($revenueByMonth as $row) {
                if (!isset($row['month'])) {
                    continue;
                }
                $revenueMap[(string) $row['month']] = (int) round((float) ($row['revenue'] ?? 0));
            }

            $labels = [];
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $monthKey = (new \DateTimeImmutable("-$i months"))->format('Y-m');
                $labels[] = $monthKey;
                $data[] = $revenueMap[$monthKey] ?? 0;
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            return [
                'labels' => [],
                'data' => []
            ];
        }
    }

    public function getNationalStats(): array
    {
        $dashboardStats = $this->getDashboardStats();
        
        return [
            'total_controls' => $dashboardStats['total_controls'],
            'total_infractions' => $dashboardStats['total_infractions'],
            'total_revenue' => $this->getTotalRevenue(),
            'active_regions' => $dashboardStats['total_regions'],
            'active_agents' => $dashboardStats['active_agents'],
            'compliance_rate' => $dashboardStats['compliance_rate']
        ];
    }

    public function getRegionsStats(): array
    {
        $regions = $this->entityManager->getRepository(Region::class)->findAll();
        $stats = [];
        
        foreach ($regions as $region) {
            $controlsCount = $this->entityManager->getRepository(Controle::class)
                ->createQueryBuilder('c')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->where('r.id = :regionId')
                ->setParameter('regionId', $region->getId())
                ->select('COUNT(c.id)')
                ->getQuery()
                ->getSingleScalarResult();
            
            $infractionsCount = $this->entityManager->getRepository(Infraction::class)
                ->createQueryBuilder('i')
                ->join('i.controle', 'c')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->where('r.id = :regionId')
                ->setParameter('regionId', $region->getId())
                ->select('COUNT(i.id)')
                ->getQuery()
                ->getSingleScalarResult();
            
            $revenue = $this->entityManager->getRepository(Amende::class)
                ->createQueryBuilder('a')
                ->select('COALESCE(SUM(a.montantPaye), 0)')
                ->join('a.infraction', 'i')
                ->join('i.controle', 'c')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->where('r.id = :regionId')
                ->andWhere('a.statut = :statut')
                ->setParameter('regionId', $region->getId())
                ->setParameter('statut', 'PAYEE')
                ->getQuery()
                ->getSingleScalarResult();
            
            $stats[] = [
                'name' => $region->getLibelle(),
                'code' => $region->getCode(),
                'controls' => $controlsCount,
                'infractions' => $infractionsCount,
                'revenue' => (int) $revenue,
                'agents' => $region->getAgents()->count(),
                'performance' => $controlsCount > 0 ? (($controlsCount - $infractionsCount) / $controlsCount) * 100 : 0
            ];
        }
        
        return $stats;
    }

    public function getMonthlyEvolution(): array
    {
        return $this->getControlsEvolution();
    }

    public function getFilteredStats(?string $period = null, ?string $region = null): array
    {
        $baseStats = $this->getNationalStats();
        
        if ($period) {
            $dateFilter = match($period) {
                'today' => new \DateTimeImmutable('today'),
                'week' => new \DateTimeImmutable('-7 days'),
                'month' => new \DateTimeImmutable('-30 days'),
                default => null
            };
            
            if ($dateFilter) {
                $controlsCount = $this->entityManager->getRepository(Controle::class)
                    ->createQueryBuilder('c')
                    ->where('c.dateControle >= :date')
                    ->setParameter('date', $dateFilter)
                    ->select('COUNT(c.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
                
                $baseStats['filtered_controls'] = $controlsCount;
            }
        }
        
        if ($region && $region !== 'all') {
            $regionEntity = $this->entityManager->getRepository(Region::class)
                ->findOneBy(['libelle' => $region]);
            
            if ($regionEntity) {
                $regionStats = $this->entityManager->getRepository(Controle::class)
                    ->createQueryBuilder('c')
                    ->join('c.brigade', 'b')
                    ->join('b.region', 'r')
                    ->where('r.id = :regionId')
                    ->setParameter('regionId', $regionEntity->getId())
                    ->select('COUNT(c.id)')
                    ->getQuery()
                    ->getSingleScalarResult();
                
                $baseStats['region_specific'] = [
                    'name' => $region,
                    'controls' => $regionStats
                ];
            }
        }
        
        return $baseStats;
    }

    private function getTotalRevenue(): int
    {
        return (int) ($this->entityManager->getRepository(Amende::class)
            ->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantPaye), 0)')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', 'PAYEE')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }
}
