<?php

namespace App\Controller\DirectionGenerale\Supervision;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/direction-generale/supervision')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class SupervisionRegionsController extends AbstractController
{
    #[Route('/', name: 'app_direction_generale_supervision')]
    public function index(): Response
    {
        // Liste des régions avec leurs informations
        $regions = [
            [
                'id' => 1,
                'name' => 'Conakry',
                'director' => 'Col. Mamadou Bah',
                'agents_count' => 456,
                'active_agents' => 434,
                'brigades_count' => 12,
                'controls_today' => 45,
                'controls_month' => 2345,
                'performance_rate' => 92.3,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 14:30'
            ],
            [
                'id' => 2,
                'name' => 'Kindia',
                'director' => 'Col. Oumar Touré',
                'agents_count' => 234,
                'active_agents' => 223,
                'brigades_count' => 8,
                'controls_today' => 23,
                'controls_month' => 1234,
                'performance_rate' => 88.7,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 13:45'
            ],
            [
                'id' => 3,
                'name' => 'Labé',
                'director' => 'Col. Sékou Condé',
                'agents_count' => 345,
                'active_agents' => 332,
                'brigades_count' => 10,
                'controls_today' => 34,
                'controls_month' => 1567,
                'performance_rate' => 85.2,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 14:15'
            ],
            [
                'id' => 4,
                'name' => 'Faranah',
                'director' => 'Col. Aissatou Bah',
                'agents_count' => 289,
                'active_agents' => 276,
                'brigades_count' => 9,
                'controls_today' => 18,
                'controls_month' => 987,
                'performance_rate' => 79.4,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 12:30'
            ],
            [
                'id' => 5,
                'name' => 'Mamou',
                'director' => 'Col. Ibrahim Camara',
                'agents_count' => 198,
                'active_agents' => 189,
                'brigades_count' => 7,
                'controls_today' => 21,
                'controls_month' => 876,
                'performance_rate' => 82.1,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 14:00'
            ],
            [
                'id' => 6,
                'name' => 'Boké',
                'director' => 'Col. Alpha Diallo',
                'agents_count' => 267,
                'active_agents' => 254,
                'brigades_count' => 8,
                'controls_today' => 28,
                'controls_month' => 1098,
                'performance_rate' => 86.5,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 13:20'
            ],
            [
                'id' => 7,
                'name' => 'N\'Zérékoré',
                'director' => 'Col. Mamadou Soumah',
                'agents_count' => 312,
                'active_agents' => 298,
                'brigades_count' => 9,
                'controls_today' => 31,
                'controls_month' => 1456,
                'performance_rate' => 90.1,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 14:45'
            ],
            [
                'id' => 8,
                'name' => 'Kankan',
                'director' => 'Col. Oumar Barry',
                'agents_count' => 355,
                'active_agents' => 341,
                'brigades_count' => 11,
                'controls_today' => 38,
                'controls_month' => 1678,
                'performance_rate' => 91.8,
                'status' => 'Actif',
                'last_activity' => '2026-01-15 14:10'
            ]
        ];

        return $this->render('direction_generale/supervision_regions.html.twig', [
            'user' => $this->getUser(),
            'regions' => $regions
        ]);
    }

    #[Route('/region/{id}', name: 'app_direction_generale_supervision_region_details')]
    public function regionDetails(int $id): Response
    {
        // Détails d'une région spécifique
        $regionDetails = [
            'id' => $id,
            'name' => 'Conakry',
            'director' => 'Col. Mamadou Bah',
            'agents_count' => 456,
            'active_agents' => 434,
            'brigades_count' => 12,
            'controls_today' => 45,
            'controls_month' => 2345,
            'infractions_today' => 12,
            'infractions_month' => 123,
            'revenue_today' => 1234567,
            'revenue_month' => 234567000,
            'performance_rate' => 92.3,
            'status' => 'Actif',
            'contact' => '+224 622 123 456',
            'email' => 'direction.conakry@police.gn'
        ];

        // Brigades de la région
        $brigades = [
            [
                'name' => 'Brigade Centre',
                'chief' => 'Chef Mamadou Diallo',
                'agents_count' => 12,
                'controls_today' => 8,
                'performance_rate' => 89.5
            ],
            [
                'name' => 'Brigade Nord',
                'chief' => 'Chef Oumar Touré',
                'agents_count' => 10,
                'controls_today' => 6,
                'performance_rate' => 85.2
            ],
            [
                'name' => 'Brigade Sud',
                'chief' => 'Chef Sékou Condé',
                'agents_count' => 11,
                'controls_today' => 7,
                'performance_rate' => 87.8
            ],
            [
                'name' => 'Brigade Est',
                'chief' => 'Chef Aissatou Bah',
                'agents_count' => 9,
                'controls_today' => 5,
                'performance_rate' => 82.3
            ],
            [
                'name' => 'Brigade Ouest',
                'chief' => 'Chef Ibrahim Camara',
                'agents_count' => 13,
                'controls_today' => 9,
                'performance_rate' => 91.2
            ]
        ];

        return $this->render('direction_generale/supervision_region_details.html.twig', [
            'user' => $this->getUser(),
            'region' => $regionDetails,
            'brigades' => $brigades
        ]);
    }

    #[Route('/brigade/{id}', name: 'app_direction_generale_supervision_brigade_details')]
    public function brigadeDetails(int $id): Response
    {
        // Détails d'une brigade spécifique
        $brigadeDetails = [
            'id' => $id,
            'name' => 'Brigade Centre',
            'chief' => 'Chef Mamadou Diallo',
            'region' => 'Conakry',
            'agents_count' => 12,
            'active_agents' => 11,
            'controls_today' => 8,
            'controls_month' => 234,
            'infractions_today' => 3,
            'infractions_month' => 45,
            'revenue_today' => 234567,
            'revenue_month' => 5678900,
            'performance_rate' => 89.5,
            'status' => 'Actif',
            'coverage_area' => 'Centre-ville + Quartiers Nord',
            'contact' => '+224 622 234 567'
        ];

        // Agents de la brigade
        $agents = [
            [
                'name' => 'Mamadou Diallo',
                'grade' => 'Brigadier-Chef',
                'status' => 'Actif',
                'controls_today' => 4,
                'performance_rate' => 95.2
            ],
            [
                'name' => 'Oumar Touré',
                'grade' => 'Adjudant',
                'status' => 'Actif',
                'controls_today' => 2,
                'performance_rate' => 88.7
            ],
            [
                'name' => 'Sékou Condé',
                'grade' => 'Brigadier',
                'status' => 'Actif',
                'controls_today' => 1,
                'performance_rate' => 76.3
            ]
        ];

        return $this->render('direction_generale/supervision_brigade_details.html.twig', [
            'user' => $this->getUser(),
            'brigade' => $brigadeDetails,
            'agents' => $agents
        ]);
    }
}
