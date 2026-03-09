<?php

namespace App\Repository;

use App\Entity\Accident;
use App\Entity\AccidentVehicle;
use App\Entity\AccidentVictim;
use App\Entity\Evacuation;
use App\Entity\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Region>
 */
class RTGStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Region::class);
    }

    public function getGlobalStatisticsFor10Years(): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        // Total accidents
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(a.id) as total_accidents')
            ->from(Accident::class, 'a')
            ->where('a.dateAccident >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit);
        
        $totalAccidents = $qb->getQuery()->getSingleScalarResult();
        
        // Total evacuations
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(e.id) as total_evacuations')
            ->from(Evacuation::class, 'e')
            ->where('e.dateEvacuation >= :dateLimit')
            ->setParameter('dateLimit', $dateLimit);
        
        $totalEvacuations = $qb->getQuery()->getSingleScalarResult();
        
        // Accidents mortels
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(a.id) as mortels')
            ->from(Accident::class, 'a')
            ->where('a.dateAccident >= :dateLimit')
            ->andWhere('a.gravite = :mortel')
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('mortel', 'MORTEL');
        
        $accidentsMortels = $qb->getQuery()->getSingleScalarResult();
        
        // Accidents graves
        $qb = $this->_em->createQueryBuilder()
            ->select('COUNT(a.id) as graves')
            ->from(Accident::class, 'a')
            ->where('a.dateAccident >= :dateLimit')
            ->andWhere('a.gravite = :grave')
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('grave', 'GRAVE');
        
        $accidentsGraves = $qb->getQuery()->getSingleScalarResult();
        
        return [
            'total_accidents' => $totalAccidents,
            'total_evacuations' => $totalEvacuations,
            'accidents_mortels' => $accidentsMortels,
            'accidents_graves' => $accidentsGraves,
            'mortalite_rate' => $totalAccidents > 0 ? ($accidentsMortels / $totalAccidents * 100) : 0,
            'grave_rate' => $totalAccidents > 0 ? ($accidentsGraves / $totalAccidents * 100) : 0,
        ];
    }

    public function getMonthlyStatsFor10Years(): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        $now = new \DateTimeImmutable();
        
        $monthlyStats = [];
        
        for ($i = 0; $i < 120; $i++) {
            $date = $dateLimit->modify('+' . $i . ' months');
            $end = $date->modify('last day of this month');
            
            // Accidents pour ce mois
            $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(a.id) as accidents_count')
                ->from(Accident::class, 'a')
                ->where('a.dateAccident >= :date AND a.dateAccident <= :end')
                ->setParameter('date', $date)
                ->setParameter('end', $end);
            
            $accidentsCount = $qb->getQuery()->getSingleScalarResult();
            
            // Évacuations pour ce mois
            $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(e.id) as evacuations_count')
                ->from(Evacuation::class, 'e')
                ->where('e.dateEvacuation >= :date AND e.dateEvacuation <= :end')
                ->setParameter('date', $date)
                ->setParameter('end', $end);
            
            $evacuationsCount = $qb->getQuery()->getSingleScalarResult();
            
            $monthlyStats[] = [
                'date' => $date->format('Y-m'),
                'accidents_count' => $accidentsCount,
                'evacuations_count' => $evacuationsCount,
                'total' => $accidentsCount + $evacuationsCount,
            ];
        }
        
        return $monthlyStats;
    }

    public function getRegionalPerformanceFor10Years(): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        // Récupérer toutes les régions
        $regions = $this->createQueryBuilder('r')
            ->select('r.id', 'r.libelle')
            ->getQuery()
            ->getResult();
        
        $regionStats = [];
        foreach ($regions as $region) {
            $regionId = $region['id'];
            $regionLibelle = $region['libelle'];
            
            // Total accidents pour cette région
            $qb = $this->_em->createQueryBuilder()
                ->select('COUNT(a.id) as total_accidents')
                ->from(Accident::class, 'a')
                ->join('a.region', 'r')
                ->where('a.dateAccident >= :dateLimit')
                ->andWhere('r.id = :regionId')
                ->setParameter('dateLimit', $dateLimit)
                ->setParameter('regionId', $regionId);
            
            $totalAccidents = $qb->getQuery()->getSingleScalarResult();
            
            // Évacuations pour cette région
            $qbEvac = $this->_em->createQueryBuilder()
                ->select('COUNT(e.id) as total_evacuations')
                ->from(Evacuation::class, 'e')
                ->join('e.region', 'r')
                ->where('e.dateEvacuation >= :dateLimit')
                ->andWhere('r.id = :regionId')
                ->setParameter('dateLimit', $dateLimit)
                ->setParameter('regionId', $regionId);
            
            $totalEvacuations = $qbEvac->getQuery()->getSingleScalarResult();
            
            // Victimes pour cette région
            $qbVictims = $this->_em->createQueryBuilder()
                ->select('COUNT(v.id) as total_victimes')
                ->from(AccidentVictim::class, 'v')
                ->join('v.accident', 'a')
                ->join('a.region', 'r')
                ->where('a.dateAccident >= :dateLimit')
                ->andWhere('r.id = :regionId')
                ->setParameter('dateLimit', $dateLimit)
                ->setParameter('regionId', $regionId);
            
            $totalVictimes = $qbVictims->getQuery()->getSingleScalarResult();
            
            // Véhicules pour cette région
            $qbVehicules = $this->_em->createQueryBuilder()
                ->select('COUNT(v.id) as total_vehicules')
                ->from(AccidentVehicle::class, 'v')
                ->join('v.accident', 'a')
                ->join('a.region', 'r')
                ->where('a.dateAccident >= :dateLimit')
                ->andWhere('r.id = :regionId')
                ->setParameter('dateLimit', $dateLimit)
                ->setParameter('regionId', $regionId);
            
            $totalVehicules = $qbVehicules->getQuery()->getSingleScalarResult();
            
            // Temps moyen de traitement
            $qbTemps = $this->_em->createQueryBuilder()
                ->select('AVG(TIMESTAMPDIFF(MINUTE, a.dateAccident, COALESCE(a.updatedAt, a.dateAccident))) as temps_moyen')
                ->from(Accident::class, 'a')
                ->join('a.region', 'r')
                ->where('a.dateAccident >= :dateLimit')
                ->andWhere('r.id = :regionId')
                ->andWhere('a.updatedAt IS NOT NULL')
                ->setParameter('dateLimit', $dateLimit)
                ->setParameter('regionId', $regionId);
            
            $tempsMoyen = $qbTemps->getQuery()->getSingleScalarResult();
            
            $regionStats[] = [
                'region' => $regionLibelle,
                'total_accidents' => $totalAccidents,
                'total_evacuations' => $totalEvacuations,
                'total_victimes' => $totalVictimes,
                'total_vehicules' => $totalVehicules,
                'temps_moyen_traitement' => $tempsMoyen ? round($tempsMoyen, 1) : 0,
                'performance' => $totalAccidents > 0 ? (($totalEvacuations / $totalAccidents) * 100) : 0,
            ];
        }
        
        return $regionStats;
    }

    public function getTopRegionsByAccidentsFor10Years(int $limit = 10): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        $qb = $this->_em->createQueryBuilder()
            ->select('r.id', 'r.libelle', 'COUNT(a.id) as total_accidents')
            ->from(Region::class, 'r')
            ->leftJoin('r.accidents', 'a')
            ->where('a.dateAccident >= :dateLimit OR a.dateAccident IS NULL')
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('total_accidents', 'DESC')
            ->setParameter('dateLimit', $dateLimit)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    public function getTopRegionsByEvacuationsFor10Years(int $limit = 10): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        $qb = $this->_em->createQueryBuilder()
            ->select('r.id', 'r.libelle', 'COUNT(e.id) as total_evacuations')
            ->from(Region::class, 'r')
            ->leftJoin('r.evacuations', 'e')
            ->where('e.dateEvacuation >= :dateLimit OR e.dateEvacuation IS NULL')
            ->groupBy('r.id', 'r.libelle')
            ->orderBy('total_evacuations', 'DESC')
            ->setParameter('dateLimit', $dateLimit)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    public function getNationalEvolutionFor10Years(): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        $qb = $this->_em->createQueryBuilder()
            ->select('DATE_FORMAT(a.dateAccident, \'%Y-%m\') as month, COUNT(a.id) as count')
            ->from(Accident::class, 'a')
            ->where('a.dateAccident >= :dateLimit')
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->setParameter('dateLimit', $dateLimit);
        
        return $qb->getQuery()->getResult();
    }

    public function getTopAccidentsFor10Years(int $limit = 10): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        $qb = $this->_em->createQueryBuilder()
            ->select('a', 'r', 'u')
            ->from(Accident::class, 'a')
            ->leftJoin('a.region', 'r')
            ->leftJoin('a.createdBy', 'u')
            ->where('a.dateAccident >= :dateLimit')
            ->orderBy('a.dateAccident', 'DESC')
            ->setParameter('dateLimit', $dateLimit)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }

    public function getTopEvacuationsFor10Years(int $limit = 10): array
    {
        $dateLimit = new \DateTimeImmutable('-10 years');
        
        $qb = $this->_em->createQueryBuilder()
            ->select('e', 'r', 'u')
            ->from(Evacuation::class, 'e')
            ->leftJoin('e.region', 'r')
            ->leftJoin('e.createdBy', 'u')
            ->where('e.dateEvacuation >= :dateLimit')
            ->orderBy('e.dateEvacuation', 'DESC')
            ->setParameter('dateLimit', $dateLimit)
            ->setMaxResults($limit);
        
        return $qb->getQuery()->getResult();
    }
}
