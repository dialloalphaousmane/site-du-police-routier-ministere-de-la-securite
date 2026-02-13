<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Rediriger selon le rôle de l'utilisateur
        $roles = $user->getRoles();
        
        // Vérifier les rôles par ordre de priorité
        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->redirectToRoute('app_dashboard_admin');
        }
        if (in_array('ROLE_DIRECTION_GENERALE', $roles)) {
            return $this->redirectToRoute('app_direction_generale_dashboard');
        }
        if (in_array('ROLE_DIRECTION_REGIONALE', $roles)) {
            return $this->redirectToRoute('app_dashboard_direction_regionale');
        }
        if (in_array('ROLE_CHEF_BRIGADE', $roles)) {
            return $this->redirectToRoute('app_dashboard_chef_brigade');
        }
        if (in_array('ROLE_AGENT', $roles)) {
            return $this->redirectToRoute('app_dashboard_agent');
        }
        
        // Redirection par défaut
        return $this->redirectToRoute('app_dashboard_agent');
    }

    #[Route('/dashboard/admin', name: 'app_dashboard_admin')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): Response
    {
        // Données directement dans le contrôleur (solution temporaire)
        $stats = [
            'users_count' => 45,
            'controls_today' => 123,
            'infractions_count' => 67,
            'amendes_total' => 456789000
        ];
        
        $controlsEvolution = [
            'labels' => ['2026-01-09', '2026-01-10', '2026-01-11', '2026-01-12', '2026-01-13', '2026-01-14'],
            'data' => [120, 95, 110, 130, 85, 145],
            'period' => '7days'
        ];
        
        $controlsByRegion = [
            'labels' => ['Conakry', 'Kindia', 'Labé', 'Faranah', 'Mamou', 'Boké', 'N\'Zérékoré', 'Kankan'],
            'data' => [450, 320, 280, 390, 310, 260, 220, 340]
        ];
        
        $infractionsByType = [
            'labels' => ['Excès de vitesse', 'Défaut de ceinture', 'Téléphone au volant', 'Défaut de casque', 'Alcoolémie'],
            'data' => [85, 45, 67, 52, 38]
        ];
        
        $monthlyRevenue = [
            'labels' => ['2025-02', '2025-03', '2025-04', '2025-05', '2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01'],
            'data' => [3200000, 3800000, 3500000, 4200000, 4100000, 4500000, 4800000, 5200000, 4900000, 5100000, 4700000, 5300000]
        ];

        return $this->render('dashboard/admin.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'controlsEvolution' => $controlsEvolution,
            'controlsByRegion' => $controlsByRegion,
            'infractionsByType' => $infractionsByType,
            'monthlyRevenue' => $monthlyRevenue,
            'controlsChart' => $controlsEvolution,
            'recent_activities' => $this->getRecentActivities()
        ]);
    }

    #[Route('/dashboard/direction-generale', name: 'app_dashboard_direction_generale')]
    #[IsGranted('ROLE_DIRECTION_GENERALE')]
    public function directionGenerale(): Response
    {
        // Données directement dans le contrôleur (solution temporaire)
        $stats = [
            'users_count' => 45,
            'controls_today' => 123,
            'infractions_count' => 67,
            'amendes_total' => 456789000
        ];
        
        $controlsEvolution = [
            'labels' => ['2026-01-09', '2026-01-10', '2026-01-11', '2026-01-12', '2026-01-13', '2026-01-14'],
            'data' => [120, 95, 110, 130, 85, 145],
            'period' => '30days'
        ];
        
        $controlsByRegion = [
            'labels' => ['Conakry', 'Kindia', 'Labé', 'Faranah', 'Mamou', 'Boké', 'N\'Zérékoré', 'Kankan'],
            'data' => [450, 320, 280, 390, 310, 260, 220, 340]
        ];
        
        $infractionsByType = [
            'labels' => ['Excès de vitesse', 'Défaut de ceinture', 'Téléphone au volant', 'Défaut de casque', 'Alcoolémie'],
            'data' => [85, 45, 67, 52, 38]
        ];
        
        $monthlyRevenue = [
            'labels' => ['2025-02', '2025-03', '2025-04', '2025-05', '2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01'],
            'data' => [3200000, 3800000, 3500000, 4200000, 4100000, 4500000, 4800000, 5200000, 4900000, 5100000, 4700000, 5300000]
        ];

        return $this->render('dashboard/direction_generale.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'controlsEvolution' => $controlsEvolution,
            'controlsByRegion' => $controlsByRegion,
            'infractionsByType' => $infractionsByType,
            'monthlyRevenue' => $monthlyRevenue
        ]);
    }

    #[Route('/dashboard/direction-regionale', name: 'app_dashboard_direction_regionale')]
    #[IsGranted('ROLE_DIRECTION_REGIONALE')]
    public function directionRegionale(): Response
    {
        // Données directement dans le contrôleur (solution temporaire)
        $stats = [
            'agents_count' => 234,
            'agents_actifs' => 224,
            'controls_month' => 1234,
            'infractions_count' => 456,
            'revenue_total' => 123456000
        ];
        
        $brigades = [
            'labels' => ['Brigade Centre', 'Brigade Nord', 'Brigade Sud', 'Brigade Est', 'Brigade Ouest'],
            'controls' => [145, 132, 128, 156, 139]
        ];

        return $this->render('dashboard/direction_regionale.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'brigades' => $brigades
        ]);
    }

    #[Route('/dashboard/chef-brigade', name: 'app_dashboard_chef_brigade')]
    #[IsGranted('ROLE_CHEF_BRIGADE')]
    public function chefBrigade(): Response
    {
        // Données directement dans le contrôleur (solution temporaire)
        $stats = [
            'team_size' => 12,
            'team_present' => 11,
            'controls_today' => 45,
            'infractions_count' => 18,
            'revenue_total' => 4567000
        ];
        
        $team = [
            'members' => [
                ['name' => 'Agent Mamadou Diallo', 'status' => 'Présent', 'controls' => 8],
                ['name' => 'Agent Oumar Touré', 'status' => 'Présent', 'controls' => 6],
                ['name' => 'Agent Sékou Condé', 'status' => 'Absent', 'controls' => 0],
                ['name' => 'Agent Aissatou Bah', 'status' => 'Présent', 'controls' => 7],
                ['name' => 'Agent Ibrahim Camara', 'status' => 'Présent', 'controls' => 5]
            ]
        ];

        return $this->render('dashboard/chef_brigade.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'team' => $team
        ]);
    }

    #[Route('/dashboard/agent', name: 'app_dashboard_agent')]
    #[IsGranted('ROLE_AGENT')]
    public function agent(): Response
    {
        // Données directement dans le contrôleur (solution temporaire)
        $stats = [
            'controls_today' => 12,
            'controls_month' => 234,
            'infractions_count' => 89,
            'revenue_total' => 8945000
        ];
        
        $controls = [
            'recent' => [
                ['id' => 1, 'date' => '2026-01-15 10:30', 'location' => 'Route Le Prince', 'vehicle' => 'Mercedes-Benz', 'infraction' => 'Excès de vitesse'],
                ['id' => 2, 'date' => '2026-01-15 09:15', 'location' => 'Pont du 8 Novembre', 'vehicle' => 'Toyota Hiace', 'infraction' => 'Défaut de ceinture'],
                ['id' => 3, 'date' => '2026-01-15 08:45', 'location' => 'Route Donka', 'vehicle' => 'Honda CR-V', 'infraction' => 'Téléphone au volant']
            ]
        ];

        return $this->render('dashboard/agent.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'controls' => $controls
        ]);
    }

    // Méthodes pour les données (simulées pour l'instant)
    private function getAdminStats(): array
    {
        // Données simulées pour éviter les erreurs de permissions
        return [
            'total_users' => 2456,
            'daily_controls' => 342,
            'infractions' => 89,
            'revenue' => 45678000
        ];
    }

    private function getDirectionGeneraleStats(): array
    {
        // Données simulées pour éviter les erreurs de permissions
        return [
            'total_controls' => 45678,
            'revenue' => 892456000,
            'active_agents' => 3456,
            'compliance_rate' => 87.3
        ];
    }

    private function getDirectionRegionaleStats(): array
    {
        // Données simulées pour éviter les erreurs de permissions
        return [
            'region_agents' => 234,
            'daily_controls' => 12,
            'monthly_controls' => 234,
            'infractions' => 34,
            'revenue' => 5678000
        ];
    }

    private function getChefBrigadeStats(): array
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getBrigadesStats');
            $data = json_decode($response->getContent(), true);
            $brigadeData = $data['brigades'][0] ?? [];
            return [
                'brigade_size' => $brigadeData['agents'] ?? 12,
                'daily_controls' => $brigadeData['controls'] ?? 45,
                'infractions' => $brigadeData['infractions'] ?? 18,
                'revenue' => $brigadeData['revenue'] ?? 4567000
            ];
        } catch (\Exception $e) {
            return [
                'brigade_size' => 12,
                'daily_controls' => 45,
                'infractions' => 18,
                'revenue' => 4567000
            ];
        }
    }

    private function getAgentStats(): array
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getControlsStats');
            $data = json_decode($response->getContent(), true);
            return [
                'daily_controls' => $data['today_controls'] ?? 12,
                'monthly_controls' => $data['this_month_controls'] ?? 234,
                'infractions' => $this->getTodayInfractions(),
                'revenue' => $this->getTodayRevenue()
            ];
        } catch (\Exception $e) {
            return [
                'daily_controls' => 12,
                'monthly_controls' => 234,
                'infractions' => 89,
                'revenue' => 8945000
            ];
        }
    }

    private function getRegionsData(): array
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getRegionsStats');
            $data = json_decode($response->getContent(), true);
            $regions = [];
            
            foreach ($data['regions'] as $region) {
                $regions[] = [
                    'name' => $region['name'],
                    'director' => 'Col. ' . $region['name'], // Simulé
                    'agents' => $region['agents'] ?? 0,
                    'monthly_controls' => $region['controls'] ?? 0,
                    'revenue' => $region['revenue'] ?? 0,
                    'performance' => 85, // Simulé
                    'status' => $region['active'] ? 'Actif' : 'Inactif'
                ];
            }
            
            return $regions ?: [
                [
                    'name' => 'Conakry',
                    'director' => 'Col. Mamadou Bah',
                    'agents' => 456,
                    'monthly_controls' => 2345,
                    'revenue' => 234567000,
                    'performance' => 92,
                    'status' => 'Excellent'
                ]
            ];
        } catch (\Exception $e) {
            return [
                [
                    'name' => 'Conakry',
                    'director' => 'Col. Mamadou Bah',
                    'agents' => 456,
                    'monthly_controls' => 2345,
                    'revenue' => 234567000,
                    'performance' => 92,
                    'status' => 'Excellent'
                ]
            ];
        }
    }

    private function getBrigadesData(): array
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getBrigadesStats');
            $data = json_decode($response->getContent(), true);
            $brigades = [];
            
            foreach ($data['brigades'] as $brigade) {
                $brigades[] = [
                    'name' => $brigade['name'],
                    'chief' => 'Chef ' . $brigade['name'], // Simulé
                    'team_size' => $brigade['agents'] ?? 0,
                    'daily_controls' => $brigade['controls'] ?? 0,
                    'coverage' => $brigade['region'] ?? 'Non spécifié',
                    'performance' => 80, // Simulé
                    'status' => $brigade['active'] ? 'Actif' : 'Inactif'
                ];
            }
            
            return $brigades ?: [
                [
                    'name' => 'Brigade Centre',
                    'chief' => 'Chef Mamadou Diallo',
                    'team_size' => 12,
                    'daily_controls' => 45,
                    'coverage' => 'Centre-ville + Quartiers Nord',
                    'performance' => 89,
                    'status' => 'Actif'
                ]
            ];
        } catch (\Exception $e) {
            return [
                [
                    'name' => 'Brigade Centre',
                    'chief' => 'Chef Mamadou Diallo',
                    'team_size' => 12,
                    'daily_controls' => 45,
                    'coverage' => 'Centre-ville + Quartiers Nord',
                    'performance' => 89,
                    'status' => 'Actif'
                ]
            ];
        }
    }

    private function getTeamData(): array
    {
        return [
            [
                'name' => 'Mamadou Diallo',
                'grade' => 'Brigadier-Chef',
                'daily_controls' => 12,
                'infractions' => 5,
                'success_rate' => 95,
                'status' => 'Actif'
            ],
            [
                'name' => 'Oumar Touré',
                'grade' => 'Adjudant',
                'daily_controls' => 10,
                'infractions' => 3,
                'success_rate' => 88,
                'status' => 'Actif'
            ],
            [
                'name' => 'Sékou Condé',
                'grade' => 'Brigadier',
                'daily_controls' => 8,
                'infractions' => 4,
                'success_rate' => 72,
                'status' => 'Actif'
            ],
            [
                'name' => 'Alpha Camara',
                'grade' => 'Agent',
                'daily_controls' => 6,
                'infractions' => 2,
                'success_rate' => 65,
                'status' => 'En formation'
            ],
            [
                'name' => 'Mamadou Bah',
                'grade' => 'Agent',
                'daily_controls' => 0,
                'infractions' => 0,
                'success_rate' => 0,
                'status' => 'Absent'
            ]
        ];
    }

    private function getAgentControls(): array
    {
        return [
            [
                'datetime' => '08/01/2026 14:30',
                'type' => 'Vitesse',
                'location' => 'Avenue du Général De Gaulle',
                'vehicles' => 15,
                'infractions' => 3,
                'amount' => 450000,
                'status' => 'Complété'
            ],
            [
                'datetime' => '08/01/2026 13:45',
                'type' => 'Documentation',
                'location' => 'Carrefour du 8 Novembre',
                'vehicles' => 12,
                'infractions' => 2,
                'amount' => 300000,
                'status' => 'Complété'
            ],
            [
                'datetime' => '08/01/2026 12:30',
                'type' => 'Alcoolémie',
                'location' => 'Pont du 8 Novembre',
                'vehicles' => 8,
                'infractions' => 1,
                'amount' => 250000,
                'status' => 'En cours'
            ],
            [
                'datetime' => '08/01/2026 11:15',
                'type' => 'Équipement',
                'location' => 'Route de Friguiagbé',
                'vehicles' => 10,
                'infractions' => 0,
                'amount' => 0,
                'status' => 'Complété'
            ],
            [
                'datetime' => '08/01/2026 10:00',
                'type' => 'Chargement',
                'location' => 'Avenue du Niger',
                'vehicles' => 6,
                'infractions' => 2,
                'amount' => 400000,
                'status' => 'Complété'
            ]
        ];
    }

    private function getRecentActivities(): array
    {
        return [
            [
                'datetime' => '08/01/2026 14:30',
                'user' => 'Agent Mamadou Diallo',
                'action' => 'Création contrôle',
                'region' => 'Conakry',
                'status' => 'Complété'
            ],
            [
                'datetime' => '08/01/2026 14:15',
                'user' => 'Chef Brigade Soumah',
                'action' => 'Validation rapport',
                'region' => 'Kindia',
                'status' => 'Validé'
            ],
            [
                'datetime' => '08/01/2026 13:45',
                'user' => 'Agent Bah',
                'action' => 'Ajout infraction',
                'region' => 'Labé',
                'status' => 'En cours'
            ],
            [
                'datetime' => '08/01/2026 13:20',
                'user' => 'Direction Régionale',
                'action' => 'Création utilisateur',
                'region' => 'Faranah',
                'status' => 'Actif'
            ],
            [
                'datetime' => '08/01/2026 12:50',
                'user' => 'Agent Touré',
                'action' => 'Modification contrôle',
                'region' => 'N\'Zérékoré',
                'status' => 'Modifié'
            ]
        ];
    }

    // Méthodes utilitaires pour les calculs de données
    private function getTodayControls(): int
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getControlsStats');
            $data = json_decode($response->getContent(), true);
            return $data['today_controls'] ?? 342;
        } catch (\Exception $e) {
            return 342;
        }
    }

    private function getTodayInfractions(): int
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getInfractionsStats');
            $data = json_decode($response->getContent(), true);
            return $data['today_infractions'] ?? 89;
        } catch (\Exception $e) {
            return 89;
        }
    }

    private function getTodayRevenue(): int
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getRevenueStats');
            $data = json_decode($response->getContent(), true);
            return $data['today_revenue'] ?? 45678000;
        } catch (\Exception $e) {
            return 45678000;
        }
    }

    private function getTotalRevenue(): int
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getRevenueStats');
            $data = json_decode($response->getContent(), true);
            return $data['total_revenue'] ?? 892456000;
        } catch (\Exception $e) {
            return 892456000;
        }
    }

    private function getActiveAgents(): int
    {
        try {
            $response = $this->forward('App\\Controller\\API\\StatsController::getUsersStats');
            $data = json_decode($response->getContent(), true);
            return $data['active_users'] ?? 3456;
        } catch (\Exception $e) {
            return 3456;
        }
    }

    private function calculateComplianceRate(): float
    {
        try {
            $controlsResponse = $this->forward('App\\Controller\\API\\StatsController::getControlsStats');
            $infractionsResponse = $this->forward('App\\Controller\\API\\StatsController::getInfractionsStats');
            
            $controls = json_decode($controlsResponse->getContent(), true);
            $infractions = json_decode($infractionsResponse->getContent(), true);
            
            $totalControls = $controls['total_controls'] ?? 45678;
            $totalInfractions = $infractions['total_infractions'] ?? 5890;
            
            if ($totalControls === 0) {
                return 0.0;
            }
            
            return round((($totalControls - $totalInfractions) / $totalControls) * 100, 1);
        } catch (\Exception $e) {
            return 87.3;
        }
    }

    private function getControlsChartData(): array
    {
        return [
            'labels' => json_encode(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']),
            'data' => json_encode([320, 342, 280, 395, 410, 380, 290])
        ];
    }

    private function getInfractionsChartData(): array
    {
        return [
            'labels' => json_encode(['Vitesse', 'Documentation', 'Alcoolémie', 'Équipement', 'Chargement']),
            'data' => json_encode([45, 23, 12, 8, 1])
        ];
    }

    private function getRegionsChartData(): array
    {
        return [
            'labels' => json_encode(['Conakry', 'Kindia', 'Labé', 'Faranah', 'N\'Zérékoré']),
            'data' => json_encode([450, 320, 280, 190, 150])
        ];
    }
}
