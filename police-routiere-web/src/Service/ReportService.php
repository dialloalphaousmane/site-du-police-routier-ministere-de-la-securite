<?php

namespace App\Service;

use App\Repository\ControleRepository;
use App\Repository\InfractionRepository;
use App\Repository\AmendeRepository;
use App\Repository\RapportRepository;

class ReportService
{
    public function __construct(
        private ControleRepository $controleRepository,
        private InfractionRepository $infractionRepository,
        private AmendeRepository $amendeRepository,
        private RapportRepository $rapportRepository
    ) {}

    public function generateMonthlyReport(\DateTime $month): array
    {
        $startDate = (clone $month)->modify('first day of this month 00:00:00');
        $endDate = (clone $month)->modify('last day of this month 23:59:59');

        return [
            'period' => 'Mensuel',
            'month' => $month->format('m/Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'controls' => $this->controleRepository->findByDateRange($startDate, $endDate),
            'infractions' => $this->infractionRepository->findByDateRange($startDate, $endDate),
            'amendes' => $this->amendeRepository->findByDateRange($startDate, $endDate),
            'summary' => [
                'total_controls' => $this->controleRepository->countByDateRange($startDate, $endDate),
                'total_infractions' => $this->infractionRepository->countByDateRange($startDate, $endDate),
                'total_amendes' => $this->amendeRepository->countByDateRange($startDate, $endDate),
                'amendes_paid' => $this->amendeRepository->countPaidByDateRange($startDate, $endDate),
            ],
        ];
    }

    public function generateRegionalReport(\DateTime $startDate, \DateTime $endDate, int $regionId): array
    {
        return [
            'period' => 'Régional',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'controls' => $this->controleRepository->findByRegionAndDateRange($regionId, $startDate, $endDate),
            'infractions' => $this->infractionRepository->findByRegionAndDateRange($regionId, $startDate, $endDate),
            'amendes' => $this->amendeRepository->findByRegionAndDateRange($regionId, $startDate, $endDate),
            'summary' => [
                'total_controls' => $this->controleRepository->countByRegionAndDateRange($regionId, $startDate, $endDate),
                'total_infractions' => $this->infractionRepository->countByRegionAndDateRange($regionId, $startDate, $endDate),
                'total_amendes' => $this->amendeRepository->countByRegionAndDateRange($regionId, $startDate, $endDate),
            ],
        ];
    }

    public function generateComplianceReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $totalControls = $this->controleRepository->countByDateRange($startDate, $endDate);
        $totalInfractions = $this->infractionRepository->countByDateRange($startDate, $endDate);
        
        $complianceRate = $totalControls > 0 
            ? (($totalControls - $totalInfractions) / $totalControls) * 100 
            : 0;

        return [
            'period' => 'Conformité',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_controls' => $totalControls,
            'total_infractions' => $totalInfractions,
            'compliance_rate' => round($complianceRate, 2),
        ];
    }

    public function generateRevenueReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $amendes = $this->amendeRepository->findByDateRange($startDate, $endDate);
        
        $totalRevenue = 0;
        $paidRevenue = 0;

        foreach ($amendes as $amende) {
            $totalRevenue += $amende->getMontant();
            if ($amende->getStatutPaiement() === 'PAYEE') {
                $paidRevenue += $amende->getMontant();
            }
        }

        return [
            'period' => 'Revenus',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_amendes_issued' => count($amendes),
            'total_revenue' => $totalRevenue,
            'paid_revenue' => $paidRevenue,
            'pending_revenue' => $totalRevenue - $paidRevenue,
            'collection_rate' => $totalRevenue > 0 ? round(($paidRevenue / $totalRevenue) * 100, 2) : 0,
        ];
    }
}
