<?php

namespace App\Service;

use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use App\Repository\UserRepository;
use App\Util\PoliceConstants;

class StatisticsService
{
    public function __construct(
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private RegionRepository $regionRepository,
        private BrigadeRepository $brigadeRepository,
        private UserRepository $userRepository
    ) {}

    public function getComprehensiveStatistics(): array
    {
        return [
            'total_controls' => $this->controleRepository->count([]),
            'total_infractions' => $this->infractionRepository->count([]),
            'total_amendes' => $this->amendeRepository->count([]),
            'total_regions' => $this->regionRepository->count([]),
            'total_brigades' => $this->brigadeRepository->count([]),
            'total_agents' => $this->userRepository->count(['roles' => 'ROLE_AGENT']),
            'amendes_paid' => $this->amendeRepository->count(['statut' => PoliceConstants::AMENDE_STATUS_PAID]),
            'amendes_pending' => $this->amendeRepository->count(['statut' => PoliceConstants::AMENDE_STATUS_PENDING]),
            'amendes_rejected' => $this->amendeRepository->count(['statut' => PoliceConstants::AMENDE_STATUS_REJECTED]),
        ];
    }

    public function getRegionalStatistics(): array
    {
        $regions = $this->regionRepository->findAll();
        $stats = [];

        foreach ($regions as $region) {
            $brigades = $region->getBrigades();
            $brigadesArray = $brigades->toArray();

            $amendesTotal = (string) ($this->amendeRepository->createQueryBuilder('a')
                ->select('COALESCE(SUM(a.montantTotal), 0)')
                ->join('a.infraction', 'i')
                ->join('i.controle', 'c')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->andWhere('r.id = :regionId')
                ->setParameter('regionId', $region->getId())
                ->getQuery()
                ->getSingleScalarResult() ?? '0');

            $stats[] = [
                'region' => $region,
                'brigades_count' => count($brigadesArray),
                'controls_count' => $this->controleRepository->countByRegion($region->getId()),
                'infractions_count' => $this->infractionRepository->countByRegion($region->getId()),
                'amendes_total' => $amendesTotal,
            ];
        }

        return $stats;
    }

    public function getReportData(string $period = 'month', ?int $regionId = null): array
    {
        $startDate = $this->getStartDateByPeriod($period);
        $endDate = new \DateTime();

        $controlsCountQb = $this->controleRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.dateControle >= :start')
            ->andWhere('c.dateControle <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($regionId) {
            $controlsCountQb->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->andWhere('r.id = :region')
                ->setParameter('region', $regionId);
        }

        $controlsCount = (int) ($controlsCountQb->getQuery()->getSingleScalarResult() ?? 0);

        $infractionsCountQb = $this->infractionRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.createdAt >= :start')
            ->andWhere('i.createdAt <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($regionId) {
            $infractionsCountQb->join('i.controle', 'c')
                ->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->andWhere('r.id = :region')
                ->setParameter('region', $regionId);
        }

        $infractionsCount = (int) ($infractionsCountQb->getQuery()->getSingleScalarResult() ?? 0);

        $amendesAggQb = $this->amendeRepository->createQueryBuilder('a')
            ->select('COALESCE(SUM(a.montantTotal), 0) AS totalAmount')
            ->addSelect('COALESCE(SUM(CASE WHEN a.statut = :paid THEN a.montantTotal ELSE 0 END), 0) AS paidAmount')
            ->addSelect('COUNT(a.id) AS amendesCount')
            ->setParameter('paid', PoliceConstants::AMENDE_STATUS_PAID)
            ->join('a.infraction', 'i')
            ->join('i.controle', 'c')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($regionId) {
            $amendesAggQb->join('c.brigade', 'b')
                ->join('b.region', 'r')
                ->andWhere('r.id = :region')
                ->setParameter('region', $regionId);
        }

        $amendesAgg = $amendesAggQb->getQuery()->getOneOrNullResult() ?? ['totalAmount' => 0, 'paidAmount' => 0, 'amendesCount' => 0];

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'controls_count' => $controlsCount,
            'infractions_count' => $infractionsCount,
            'amendes_count' => (int) ($amendesAgg['amendesCount'] ?? 0),
            'amendes_total_amount' => (float) ($amendesAgg['totalAmount'] ?? 0),
            'amendes_paid_amount' => (float) ($amendesAgg['paidAmount'] ?? 0),
        ];
    }

    public function getDailyStatistics(\DateTime $date, ?int $regionId = null): array
    {
        $startDate = (clone $date)->modify('00:00:00');
        $endDate = (clone $date)->modify('23:59:59');

        $qb = $this->controleRepository->createQueryBuilder('c')
            ->where('c.dateControle >= :start')
            ->andWhere('c.dateControle <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate);

        if ($regionId) {
            $qb->leftJoin('c.brigade', 'b')
                ->leftJoin('b.region', 'r')
                ->andWhere('r.id = :region')
                ->setParameter('region', $regionId);
        }

        return [
            'date' => $date->format('Y-m-d'),
            'controls' => count($qb->getQuery()->getResult()),
            'infractions' => $this->infractionRepository->countByDate($date, $regionId),
            'amendes' => $this->amendeRepository->countByDate($date, $regionId),
        ];
    }

    private function getStartDateByPeriod(string $period): \DateTime
    {
        $date = new \DateTime();
        
        return match ($period) {
            'week' => $date->modify('-7 days'),
            'month' => $date->modify('-30 days'),
            'quarter' => $date->modify('-90 days'),
            'year' => $date->modify('-365 days'),
            default => $date->modify('-30 days'),
        };
    }
}
