<?php

namespace App\Service;

use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\RegionRepository;
use App\Repository\BrigadeRepository;
use App\Repository\UserRepository;

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
            'amendes_paid' => $this->amendeRepository->count(['statutPaiement' => 'PAYEE']),
            'amendes_pending' => $this->amendeRepository->count(['statutPaiement' => 'EN_ATTENTE']),
            'amendes_rejected' => $this->amendeRepository->count(['statutPaiement' => 'REJETEE']),
        ];
    }

    public function getRegionalStatistics(): array
    {
        $regions = $this->regionRepository->findAll();
        $stats = [];

        foreach ($regions as $region) {
            $brigades = $region->getBrigades();
            $brigadesArray = $brigades->toArray();

            $stats[] = [
                'region' => $region,
                'brigades_count' => count($brigadesArray),
                'controls_count' => $this->controleRepository->countByRegion($region->getId()),
                'infractions_count' => $this->infractionRepository->countByRegion($region->getId()),
                'amendes_total' => $this->amendeRepository->sumByRegion($region->getId()),
            ];
        }

        return $stats;
    }

    public function getReportData(string $period = 'month', ?int $regionId = null): array
    {
        $startDate = $this->getStartDateByPeriod($period);
        $endDate = new \DateTime();

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

        $controls = $qb->getQuery()->getResult();

        $infractions = $this->infractionRepository->findByDateRange($startDate, $endDate, $regionId);
        $amendes = $this->amendeRepository->findByDateRange($startDate, $endDate, $regionId);

        return [
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'controls_count' => count($controls),
            'infractions_count' => count($infractions),
            'amendes_count' => count($amendes),
            'amendes_total_amount' => array_sum(array_map(fn($a) => $a->getMontant(), $amendes)),
            'amendes_paid_amount' => array_sum(array_map(fn($a) => $a->getStatutPaiement() === 'PAYEE' ? $a->getMontant() : 0, $amendes)),
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
