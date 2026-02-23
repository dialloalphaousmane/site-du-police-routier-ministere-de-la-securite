<?php

namespace App\Controller\DirectionGenerale\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\DirectionGenerale\StatisticsService;

#[Route('/dashboard/direction-generale')]
#[IsGranted('ROLE_DIRECTION_GENERALE')]
class DashboardController extends AbstractController
{
    private StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    #[Route('/', name: 'app_direction_generale_dashboard')]
    public function index(): Response
    {
        // Récupérer les données via le service
        $stats = $this->statisticsService->getDashboardStats();
        $controlsEvolution = $this->statisticsService->getControlsEvolution();
        $controlsByRegion = $this->statisticsService->getControlsByRegion();
        $infractionsByType = $this->statisticsService->getInfractionsByType();
        $monthlyRevenue = $this->statisticsService->getMonthlyRevenue();
        $regionsStats = $this->statisticsService->getRegionsStats();

        return $this->render('direction_generale/dashboard/dashboard.html.twig', [
            'user' => $this->getUser(),
            'stats' => $stats,
            'controlsEvolution' => $controlsEvolution,
            'controlsByRegion' => $controlsByRegion,
            'infractionsByType' => $infractionsByType,
            'monthlyRevenue' => $monthlyRevenue,
            'regionsStats' => $regionsStats
        ]);
    }

    #[Route('/refresh-stats', name: 'app_direction_generale_dashboard_refresh_stats')]
    public function refreshStats(): Response
    {
        // Endpoint pour rafraîchir les statistiques en AJAX
        $stats = $this->statisticsService->getDashboardStats();
        
        return $this->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    #[Route('/export', name: 'app_direction_generale_dashboard_export')]
    public function export(): Response
    {
        // Logique d'export par défaut (Excel)
        $this->addFlash('success', 'Dashboard exporté avec succès');
        
        return $this->redirectToRoute('app_direction_generale_dashboard');
    }

    #[Route('/export-pdf', name: 'app_direction_generale_dashboard_export_pdf')]
    public function exportPdf(): Response
    {
        // Logique d'export PDF
        $this->addFlash('success', 'Dashboard exporté en PDF avec succès');
        
        return $this->redirectToRoute('app_direction_generale_dashboard');
    }

    #[Route('/export-excel', name: 'app_direction_generale_dashboard_export_excel')]
    public function exportExcel(): Response
    {
        // Logique d'export Excel
        $this->addFlash('success', 'Dashboard exporté en Excel avec succès');
        
        return $this->redirectToRoute('app_direction_generale_dashboard');
    }
}
