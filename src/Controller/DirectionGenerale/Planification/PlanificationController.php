<?php

namespace App\Controller\DirectionGenerale\Planification;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/dashboard/direction-generale/planification')]
// #[IsGranted('ROLE_DIRECTION_GENERALE')]
class PlanificationController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'app_direction_generale_planification')]
    public function index(): Response
    {
        // Plans actifs
        $activePlans = [
            [
                'id' => 1,
                'title' => 'Plan de Contrôle Renforcé - Conakry',
                'start_date' => '2026-01-01',
                'end_date' => '2026-03-31',
                'status' => 'En cours',
                'progress' => 65,
                'objectives_count' => 8,
                'completed_objectives' => 5,
                'budget' => 50000000,
                'budget_used' => 32500000
            ],
            [
                'id' => 2,
                'title' => 'Opération Sécurité Routière - Kindia',
                'start_date' => '2026-01-15',
                'end_date' => '2026-02-28',
                'status' => 'En cours',
                'progress' => 40,
                'objectives_count' => 6,
                'completed_objectives' => 2,
                'budget' => 25000000,
                'budget_used' => 10000000
            ]
        ];

        // Plans à venir
        $upcomingPlans = [
            [
                'id' => 3,
                'title' => 'Planification Spéciale Fêtes',
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-15',
                'status' => 'Planifié',
                'progress' => 0,
                'objectives_count' => 4,
                'completed_objectives' => 0,
                'budget' => 15000000,
                'budget_used' => 0
            ]
        ];

        // Plans archivés
        $archivedPlans = [
            [
                'id' => 4,
                'title' => 'Plan Annuel 2025',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'status' => 'Terminé',
                'progress' => 100,
                'objectives_count' => 24,
                'completed_objectives' => 24,
                'budget' => 200000000,
                'budget_used' => 185000000
            ]
        ];

        return $this->render('direction_generale/planification/planification.html.twig', [
            'user' => $this->getUser(),
            'activePlans' => $activePlans,
            'upcomingPlans' => $upcomingPlans,
            'archivedPlans' => $archivedPlans
        ]);
    }

    #[Route('/nouveau', name: 'app_direction_generale_planification_nouveau')]
    public function nouveau(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Logique de création du plan
            $planData = [
                'title' => $request->request->get('title'),
                'description' => $request->request->get('description'),
                'start_date' => $request->request->get('start_date'),
                'end_date' => $request->request->get('end_date'),
                'budget' => $request->request->get('budget'),
                'objectives' => $request->request->get('objectives', [])
            ];
            
            // En production, sauvegarder dans la base de données
            $this->addFlash('success', 'Plan créé avec succès.');
            
            return $this->redirectToRoute('app_direction_generale_planification');
        }
        
        return $this->render('direction_generale/planification/planification_nouveau.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/details/{id}', name: 'app_direction_generale_planification_details')]
    public function details(int $id): Response
    {
        // Détails du plan
        $plan = [
            'id' => $id,
            'title' => 'Plan de Contrôle Renforcé - Conakry',
            'description' => 'Plan de contrôle intensifié dans la zone de Conakry pour réduire les infractions',
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'En cours',
            'progress' => 65,
            'budget' => 50000000,
            'budget_used' => 32500000,
            'created_at' => '2025-12-15',
            'created_by' => 'Directeur Général'
        ];

        // Objectifs du plan
        $objectives = [
            [
                'id' => 1,
                'title' => 'Augmenter les contrôles de 30%',
                'description' => 'Passer de 1000 à 1300 contrôles par semaine',
                'progress' => 80,
                'status' => 'En cours',
                'deadline' => '2026-02-15'
            ],
            [
                'id' => 2,
                'title' => 'Réduire les accidents de 20%',
                'description' => 'Objectif de sécurité routière',
                'progress' => 60,
                'status' => 'En cours',
                'deadline' => '2026-03-31'
            ],
            [
                'id' => 3,
                'title' => 'Former 50 agents',
                'description' => 'Formation aux nouvelles procédures',
                'progress' => 100,
                'status' => 'Terminé',
                'deadline' => '2026-01-31'
            ]
        ];

        // Événements du calendrier
        $events = [
            [
                'date' => '2026-01-20',
                'title' => 'Réunion de suivi',
                'type' => 'meeting',
                'description' => 'Point sur le progrès du plan'
            ],
            [
                'date' => '2026-02-01',
                'title' => 'Déploiement renforcé',
                'type' => 'deployment',
                'description' => 'Déploiement des effectifs supplémentaires'
            ],
            [
                'date' => '2026-02-15',
                'title' => 'Évaluation intermédiaire',
                'type' => 'evaluation',
                'description' => 'Évaluation des résultats à mi-parcours'
            ]
        ];

        return $this->render('direction_generale/planification/planification_details.html.twig', [
            'user' => $this->getUser(),
            'plan' => $plan,
            'objectives' => $objectives,
            'events' => $events
        ]);
    }

    #[Route('/modifier/{id}', name: 'app_direction_generale_planification_modifier')]
    public function modifier(int $id, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Logique de modification du plan
            $this->addFlash('success', 'Plan modifié avec succès.');
            
            return $this->redirectToRoute('app_direction_generale_planification');
        }
        
        return $this->render('direction_generale/planification/planification_modifier.html.twig', [
            'user' => $this->getUser(),
            'planId' => $id
        ]);
    }

    #[Route('/calendrier', name: 'app_direction_generale_planification_calendrier')]
    public function calendrier(): Response
    {
        // Événements du calendrier pour tous les plans
        $events = [
            [
                'id' => 1,
                'title' => 'Plan Contrôle Renforcé - Début',
                'date' => '2026-01-01',
                'type' => 'start',
                'color' => '#28a745',
                'plan_id' => 1
            ],
            [
                'id' => 2,
                'title' => 'Opération Sécurité Routière - Début',
                'date' => '2026-01-15',
                'type' => 'start',
                'color' => '#007bff',
                'plan_id' => 2
            ],
            [
                'id' => 3,
                'title' => 'Réunion de suivi - Conakry',
                'date' => '2026-01-20',
                'type' => 'meeting',
                'color' => '#ffc107',
                'plan_id' => 1
            ],
            [
                'id' => 4,
                'title' => 'Planification Spéciale Fêtes - Début',
                'date' => '2026-02-01',
                'type' => 'start',
                'color' => '#dc3545',
                'plan_id' => 3
            ]
        ];

        return $this->render('direction_generale/planification/planification_calendrier.html.twig', [
            'user' => $this->getUser(),
            'events' => $events
        ]);
    }

    #[Route('/export', name: 'app_direction_generale_planification_export')]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'excel');
        $planId = $request->query->get('plan');
        
        // Logique d'export du plan
        $this->addFlash('success', "Plan exporté en $format avec succès.");
        
        return $this->redirectToRoute('app_direction_generale_planification');
    }

    #[Route('/terminer/{id}', name: 'app_direction_generale_planification_terminer', methods: ['POST'])]
    public function terminer(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('plan_status'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_direction_generale_planification_details', ['id' => $id]);
        }

        $this->addFlash('success', 'Plan marqué comme terminé.');

        return $this->redirectToRoute('app_direction_generale_planification_details', ['id' => $id]);
    }

    #[Route('/pause/{id}', name: 'app_direction_generale_planification_pause', methods: ['POST'])]
    public function pause(int $id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('plan_status'.$id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_direction_generale_planification_details', ['id' => $id]);
        }

        $this->addFlash('success', 'Plan mis en pause.');

        return $this->redirectToRoute('app_direction_generale_planification_details', ['id' => $id]);
    }

    #[Route('/archiver/{id}', name: 'app_direction_generale_planification_archiver', methods: ['POST'])]
    public function archiver(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('archiver_plan'.$id, $request->request->get('_token'))) {
            // Logique d'archivage du plan
            $this->addFlash('success', 'Plan archivé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('app_direction_generale_planification');
    }

    #[Route('/supprimer/{id}', name: 'app_direction_generale_planification_supprimer')]
    public function supprimer(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('supprimer_plan'.$id, $request->request->get('_token'))) {
            // Logique de suppression du plan
            $this->addFlash('success', 'Plan supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('app_direction_generale_planification');
    }
}
