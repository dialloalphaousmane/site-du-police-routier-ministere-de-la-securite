<?php

namespace App\Controller\DirectionGenerale\Statistiques;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/direction-generale/statistiques')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class StatistiquesNationalesController extends AbstractController
{
    #[Route('/', name: 'app_direction_generale_statistiques')]
    public function index(): Response
    {
        // Statistiques nationales
        $nationalStats = [
            'total_agents' => 3456,
            'active_agents' => 3234,
            'total_controls' => 45678,
            'monthly_controls' => 12345,
            'total_infractions' => 8901,
            'monthly_infractions' => 567,
            'total_revenue' => 892456000,
            'monthly_revenue' => 45678000,
            'compliance_rate' => 87.3
        ];

        // Statistiques par région
        $regionsStats = [
            [
                'name' => 'Conakry',
                'agents' => 456,
                'controls' => 2345,
                'infractions' => 123,
                'revenue' => 234567000,
                'performance' => 92
            ],
            [
                'name' => 'Kindia',
                'agents' => 234,
                'controls' => 1234,
                'infractions' => 89,
                'revenue' => 123456000,
                'performance' => 88
            ],
            [
                'name' => 'Labé',
                'agents' => 345,
                'controls' => 1567,
                'infractions' => 98,
                'revenue' => 167890000,
                'performance' => 85
            ],
            [
                'name' => 'Faranah',
                'agents' => 289,
                'controls' => 987,
                'infractions' => 67,
                'revenue' => 98765000,
                'performance' => 79
            ],
            [
                'name' => 'Mamou',
                'agents' => 198,
                'controls' => 876,
                'infractions' => 54,
                'revenue' => 87654000,
                'performance' => 82
            ],
            [
                'name' => 'Boké',
                'agents' => 267,
                'controls' => 1098,
                'infractions' => 76,
                'revenue' => 109876000,
                'performance' => 86
            ],
            [
                'name' => 'N\'Zérékoré',
                'agents' => 312,
                'controls' => 1456,
                'infractions' => 87,
                'revenue' => 145678000,
                'performance' => 90
            ],
            [
                'name' => 'Kankan',
                'agents' => 355,
                'controls' => 1678,
                'infractions' => 101,
                'revenue' => 167890000,
                'performance' => 91
            ]
        ];

        // Évolution mensuelle
        $monthlyEvolution = [
            'labels' => ['2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01'],
            'controls' => [8900, 9200, 9800, 10200, 10800, 11500, 12100, 12345],
            'infractions' => [423, 445, 467, 489, 512, 534, 556, 567],
            'revenue' => [32000000, 33000000, 35000000, 38000000, 41000000, 43000000, 45000000, 45678000]
        ];

        return $this->render('direction_generale/statistiques_nationales.html.twig', [
            'user' => $this->getUser(),
            'nationalStats' => $nationalStats,
            'regionsStats' => $regionsStats,
            'monthlyEvolution' => $monthlyEvolution
        ]);
    }

    #[Route('/details/{region}', name: 'app_direction_generale_statistiques_region')]
    public function regionDetails(string $region): Response
    {
        // Détails pour une région spécifique
        $regionDetails = [
            'name' => $region,
            'agents' => 456,
            'active_agents' => 434,
            'brigades' => 12,
            'controls_today' => 45,
            'controls_month' => 2345,
            'infractions_today' => 12,
            'infractions_month' => 123,
            'revenue_today' => 1234567,
            'revenue_month' => 234567000,
            'performance_rate' => 92.3
        ];

        return $this->render('direction_generale/statistiques_region_details.html.twig', [
            'user' => $this->getUser(),
            'region' => $regionDetails
        ]);
    }
}
