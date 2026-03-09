<?php

namespace App\Service;

use App\Entity\Accident;
use App\Repository\AccidentRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use Doctrine\ORM\EntityManagerInterface;

class AccidentStatisticsService
{
    public function __construct(
        private AccidentRepository $accidentRepository,
        private RegionRepository $regionRepository,
        private BrigadeRepository $brigadeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getGeneralStatistics(int $years = 10, ?string $region = null, ?string $brigade = null): array
    {
        $startDate = new \DateTimeImmutable("-{$years} years");
        $endDate = new \DateTimeImmutable();

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->select('COUNT(a.id) as total_accidents')
            ->addSelect('SUM(a.nbVictimes) as total_victimes')
            ->addSelect('SUM(a.nbMorts) as total_morts')
            ->addSelect('SUM(a.nbBlessesGraves) as total_blesses_graves')
            ->addSelect('SUM(a.nbBlessesLegers) as total_blesses_legers')
            ->addSelect('COUNT(CASE WHEN a.gravite = :mortel THEN 1 END) as accidents_mortels')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('mortel', 'MORTEL');

        if ($region) {
            $qb->join('a.region', 'r')
               ->where('r.code = :regionCode')
               ->setParameter('regionCode', $region);
        }

        if ($brigade) {
            $qb->join('a.brigade', 'b')
               ->where('b.code = :brigadeCode')
               ->setParameter('brigadeCode', $brigade);
        }

        $result = $qb->getQuery()->getSingleResult();

        $totalVictimes = (int) $result['total_victimes'];
        $totalMorts = (int) $result['total_morts'];
        
        return [
            'total_accidents' => (int) $result['total_accidents'],
            'total_victimes' => $totalVictimes,
            'total_morts' => $totalMorts,
            'total_blesses_graves' => (int) $result['total_blesses_graves'],
            'total_blesses_legers' => (int) $result['total_blesses_legers'],
            'taux_mortalite' => $totalVictimes > 0 ? round(($totalMorts / $totalVictimes) * 100, 2) : 0,
            'accidents_mortels' => (int) $result['accidents_mortels'],
            'total_evacuations' => $this->countEvacuations($startDate, $endDate, $region, $brigade),
        ];
    }

    public function getYearlyStatistics(int $years = 10, ?string $region = null, ?string $brigade = null): array
    {
        $yearlyStats = [];
        $currentYear = (int) date('Y');

        for ($i = $years - 1; $i >= 0; $i--) {
            $year = $currentYear - $i;
            $startDate = new \DateTimeImmutable("$year-01-01");
            $endDate = new \DateTimeImmutable("$year-12-31");

            $qb = $this->accidentRepository->createQueryBuilder('a')
                ->select('COUNT(a.id) as accidents')
                ->addSelect('SUM(a.nbVictimes) as victimes')
                ->addSelect('SUM(a.nbMorts) as morts')
                ->addSelect('SUM(a.nbBlessesGraves) as blesses_graves')
                ->addSelect('SUM(a.nbBlessesLegers) as blesses_legers')
                ->where('a.dateAccident BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);

            if ($region) {
                $qb->join('a.region', 'r')
                   ->where('r.code = :regionCode')
                   ->setParameter('regionCode', $region);
            }

            if ($brigade) {
                $qb->join('a.brigade', 'b')
                   ->where('b.code = :brigadeCode')
                   ->setParameter('brigadeCode', $brigade);
            }

            $result = $qb->getQuery()->getSingleResult();

            $victimes = (int) $result['victimes'];
            $morts = (int) $result['morts'];

            $yearlyStats[$year] = [
                'accidents' => (int) $result['accidents'],
                'victimes' => $victimes,
                'morts' => $morts,
                'blesses_graves' => (int) $result['blesses_graves'],
                'blesses_legers' => (int) $result['blesses_legers'],
                'taux_mortalite' => $victimes > 0 ? round(($morts / $victimes) * 100, 2) : 0,
            ];
        }

        return $yearlyStats;
    }

    public function getTopCauses(int $years = 10, ?string $region = null, ?string $brigade = null): array
    {
        $startDate = new \DateTimeImmutable("-{$years} years");
        $endDate = new \DateTimeImmutable();

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.causePrincipale as cause, COUNT(a.id) as count')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->groupBy('a.causePrincipale')
            ->orderBy('count', 'DESC')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($region) {
            $qb->join('a.region', 'r')
               ->where('r.code = :regionCode')
               ->setParameter('regionCode', $region);
        }

        if ($brigade) {
            $qb->join('a.brigade', 'b')
               ->where('b.code = :brigadeCode')
               ->setParameter('brigadeCode', $brigade);
        }

        $results = $qb->getQuery()->getResult();
        
        $causes = [];
        foreach ($results as $result) {
            $causes[Accident::CAUSES[$result['cause']] ?? $result['cause']] = (int) $result['count'];
        }

        return $causes;
    }

    public function getRecommendedSolutions(int $years = 10, ?string $region = null, ?string $brigade = null): array
    {
        $topCauses = $this->getTopCauses($years, $region, $brigade);
        $solutions = [];

        foreach ($topCauses as $cause => $count) {
            $solutions = array_merge($solutions, $this->getSolutionsForCause($cause, $count));
        }

        return array_unique($solutions);
    }

    private function getSolutionsForCause(string $cause, int $frequency): array
    {
        $solutions = match($cause) {
            'Vitesse excessive' => [
                "🚦 Installation de radars automatiques dans les zones à risque",
                "📢 Campagnes de sensibilisation sur les dangers de la vitesse",
                "👮 Augmentation des contrôles de vitesse aux heures de pointe",
                "🛣️ Aménagement de ralentisseurs dans les zones sensibles"
            ],
            'Conduite sous influence' => [
                "🚔 Contrôles d'alcoolémie renforcés le week-end",
                "📱 Application mobile pour éthylotests",
                "🏫 Sensibilisation dans les écoles et entreprises",
                "⚖️ Peines plus sévères pour récidivistes"
            ],
            'Fatigue ou somnolence' => [
                "🛣️ Création d'aires de repos tous les 50km",
                "📱 Application de détection de fatigue",
                "📺 Campagnes sur l'importance des pauses",
                "⏰ Limitation des heures de conduite pour professionnels"
            ],
            'Route mouillée' => [
                "🔧 Amélioration du drainage des routes",
                "🚘 Signalisation adaptée aux conditions météo",
                "📢 Information en temps réel sur l'état des routes",
                "🛞 Obligation de pneus adaptés en saison des pluies"
            ],
            'Mauvais état de la route' => [
                "🔧 Programme d'entretien prioritaire des routes",
                "📱 Application de signalement des nids-de-poule",
                "🚘 Signalisation claire des zones dangereuses",
                "💰 Budget dédié à la rénovation routière"
            ],
            default => [
                "📊 Analyse approfondie des causes spécifiques",
                "👮 Renforcement des contrôles ciblés",
                "📢 Campagnes de prévention adaptées",
                "🔍 Étude des black-spots accidentologiques"
            ]
        };

        return $solutions;
    }

    public function getMonthlyStatistics(int $year, ?string $region = null, ?string $brigade = null): array
    {
        $monthlyStats = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = new \DateTimeImmutable("$year-$month-01");
            $endDate = $startDate->modify('last day of 23:59:59');

            $qb = $this->accidentRepository->createQueryBuilder('a')
                ->select('COUNT(a.id) as accidents')
                ->addSelect('SUM(a.nbVictimes) as victimes')
                ->addSelect('SUM(a.nbMorts) as morts')
                ->where('a.dateAccident BETWEEN :startDate AND :endDate')
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);

            if ($region) {
                $qb->join('a.region', 'r')
                   ->where('r.code = :regionCode')
                   ->setParameter('regionCode', $region);
            }

            if ($brigade) {
                $qb->join('a.brigade', 'b')
                   ->where('b.code = :brigadeCode')
                   ->setParameter('brigadeCode', $brigade);
            }

            $result = $qb->getQuery()->getSingleResult();

            $monthlyStats[$month] = [
                'accidents' => (int) $result['accidents'],
                'victimes' => (int) $result['victimes'],
                'morts' => (int) $result['morts'],
            ];
        }

        return $monthlyStats;
    }

    public function getRegionStatistics(int $years = 10): array
    {
        $regions = $this->regionRepository->findAll();
        $regionStats = [];

        foreach ($regions as $region) {
            $stats = $this->getGeneralStatistics($years, $region->getCode());
            $regionStats[$region->getLibelle()] = $stats;
        }

        return $regionStats;
    }

    public function getBlackspots(int $limit = 10): array
    {
        $startDate = new \DateTimeImmutable("-2 years");
        $endDate = new \DateTimeImmutable();

        $qb = $this->accidentRepository->createQueryBuilder('a')
            ->select('a.localisation, COUNT(a.id) as accident_count, SUM(a.nbVictimes) as total_victimes')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->groupBy('a.localisation')
            ->having('accident_count > 1')
            ->orderBy('accident_count', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    private function countEvacuations(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate, ?string $region, ?string $brigade): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from('App\Entity\Evacuation', 'e')
            ->join('e.accident', 'a')
            ->where('a.dateAccident BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($region) {
            $qb->join('a.region', 'r')
               ->where('r.code = :regionCode')
               ->setParameter('regionCode', $region);
        }

        if ($brigade) {
            $qb->join('a.brigade', 'b')
               ->where('b.code = :brigadeCode')
               ->setParameter('brigadeCode', $brigade);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getEvolutionTrend(int $years = 5): array
    {
        $yearlyStats = $this->getYearlyStatistics($years);
        $trends = [];

        $yearsArray = array_keys($yearlyStats);
        sort($yearsArray);

        for ($i = 1; $i < count($yearsArray); $i++) {
            $prevYear = $yearsArray[$i - 1];
            $currentYear = $yearsArray[$i];

            $prevAccidents = $yearlyStats[$prevYear]['accidents'];
            $currentAccidents = $yearlyStats[$currentYear]['accidents'];

            $trend = $prevAccidents > 0 ? 
                round((($currentAccidents - $prevAccidents) / $prevAccidents) * 100, 2) : 0;

            $trends[$currentYear] = [
                'year' => $currentYear,
                'accidents' => $currentAccidents,
                'trend_percentage' => $trend,
                'trend_label' => $trend > 0 ? '📈' : ($trend < 0 ? '📉' : '➡️'),
            ];
        }

        return $trends;
    }
}
