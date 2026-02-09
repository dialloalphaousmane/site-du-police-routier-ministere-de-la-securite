<?php

namespace App\Controller\DirectionGenerale\Performance;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\DirectionGenerale\PerformanceService;
use App\Entity\Region;

#[Route('/dashboard/direction-generale/performance')]
// #[IsGranted('ROLE_DIRECTION_GENERALE')]
class PerformanceController extends AbstractController
{
    private PerformanceService $performanceService;

    public function __construct(PerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    #[Route('/', name: 'app_direction_generale_performance')]
    public function index(): Response
    {
        $globalPerformance = $this->performanceService->getGlobalPerformance();
        $regionalPerformance = $this->performanceService->getRegionalPerformance();
        $topPerformers = $this->performanceService->getTopPerformers();
        $monthlyEvolution = $this->performanceService->getMonthlyEvolution();

        return $this->render('direction_generale/performance/performance.html.twig', [
            'user' => $this->getUser(),
            'globalPerformance' => $globalPerformance,
            'regionalPerformance' => $regionalPerformance,
            'topPerformers' => $topPerformers,
            'monthlyEvolution' => $monthlyEvolution
        ]);
    }

    #[Route('/region/{id}', name: 'app_direction_generale_performance_region')]
    public function regionPerformance(Region $region): Response
    {
        $regionData = $this->performanceService->getRegionPerformance($region->getId());
        
        return $this->render('direction_generale/performance/performance_region.html.twig', [
            'user' => $this->getUser(),
            'region' => $region,
            'performanceData' => $regionData
        ]);
    }

    #[Route('/indicateurs', name: 'app_direction_generale_performance_indicateurs')]
    public function indicateurs(): Response
    {
        $indicators = $this->performanceService->getPerformanceIndicators();
        
        return $this->render('direction_generale/performance/performance_indicateurs.html.twig', [
            'user' => $this->getUser(),
            'indicators' => $indicators
        ]);
    }

    #[Route('/objectifs', name: 'app_direction_generale_performance_objectifs')]
    public function objectifs(): Response
    {
        $objectives = $this->performanceService->getObjectives();
        
        return $this->render('direction_generale/performance/performance_objectifs.html.twig', [
            'user' => $this->getUser(),
            'objectives' => $objectives
        ]);
    }

    #[Route('/objectifs/mettre-a-jour', name: 'app_direction_generale_performance_objectifs_update', methods: ['POST'])]
    public function updateObjectifs(Request $request): Response
    {
        $objectives = $request->request->all('objectives');
        
        // Logique de mise à jour des objectifs
        $this->performanceService->updateObjectives($objectives);
        
        $this->addFlash('success', 'Objectifs mis à jour avec succès.');
        
        return $this->redirectToRoute('app_direction_generale_performance_objectifs');
    }

    #[Route('/export', name: 'app_direction_generale_performance_export')]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'excel');
        $type = $request->query->get('type', 'global');
        
        // Logique d'export des données de performance
        $this->addFlash('success', "Rapport de performance exporté en $format avec succès.");
        
        return $this->redirectToRoute('app_direction_generale_performance');
    }

    #[Route('/refresh', name: 'app_direction_generale_performance_refresh')]
    public function refresh(): Response
    {
        // Endpoint pour rafraîchir les données de performance en AJAX
        $globalPerformance = $this->performanceService->getGlobalPerformance();
        
        return $this->json([
            'success' => true,
            'data' => $globalPerformance
        ]);
    }
}
