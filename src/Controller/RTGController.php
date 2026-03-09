<?php

namespace App\Controller;

use App\Repository\RTGStatisticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rtg')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class RTGController extends AbstractController
{
    private RTGStatisticsRepository $rtgStatisticsRepository;

    public function __construct(RTGStatisticsRepository $rtgStatisticsRepository)
    {
        $this->rtgStatisticsRepository = $rtgStatisticsRepository;
    }

    #[Route('/dashboard', name: 'app_rtg_dashboard')]
    public function dashboard(): Response
    {
        // Statistiques globales pour les 10 dernières années
        $globalStats = $this->rtgStatisticsRepository->getGlobalStatisticsFor10Years();
        
        // Évolution mensuelle pour les 10 dernières années
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();
        
        // Performance par région pour les 10 dernières années
        $regionalPerformance = $this->rtgStatisticsRepository->getRegionalPerformanceFor10Years();
        
        // Top régions par accidents
        $topRegionsAccidents = $this->rtgStatisticsRepository->getTopRegionsByAccidentsFor10Years(10);
        
        // Top régions par évacuations
        $topRegionsEvacuations = $this->rtgStatisticsRepository->getTopRegionsByEvacuationsFor10Years(10);
        
        // Évolution nationale
        $nationalEvolution = $this->rtgStatisticsRepository->getNationalEvolutionFor10Years();
        
        // Top accidents récents
        $topAccidents = $this->rtgStatisticsRepository->getTopAccidentsFor10Years(10);
        
        // Top évacuations récentes
        $topEvacuations = $this->rtgStatisticsRepository->getTopEvacuationsFor10Years(10);

        return $this->render('rtg/dashboard.html.twig', [
            'globalStats' => $globalStats,
            'monthlyStats' => $monthlyStats,
            'regionalPerformance' => $regionalPerformance,
            'topRegionsAccidents' => $topRegionsAccidents,
            'topRegionsEvacuations' => $topRegionsEvacuations,
            'nationalEvolution' => $nationalEvolution,
            'topAccidents' => $topAccidents,
            'topEvacuations' => $topEvacuations,
        ]);
    }

    #[Route('/regions', name: 'app_rtg_regions')]
    public function regions(): Response
    {
        // Statistiques par région pour les 10 dernières années
        $regionalPerformance = $this->rtgStatisticsRepository->getRegionalPerformanceFor10Years();
        
        // Évolution par région
        $nationalEvolution = $this->rtgStatisticsRepository->getNationalEvolutionFor10Years();
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();

        return $this->render('rtg/regions.html.twig', [
            'regionalPerformance' => $regionalPerformance,
            'nationalEvolution' => $nationalEvolution,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    #[Route('/evolution', name: 'app_rtg_evolution')]
    public function evolution(): Response
    {
        // Évolution nationale pour les 10 dernières années
        $nationalEvolution = $this->rtgStatisticsRepository->getNationalEvolutionFor10Years();
        
        // Statistiques mensuelles
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();

        return $this->render('rtg/evolution.html.twig', [
            'nationalEvolution' => $nationalEvolution,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    #[Route('/performance', name: 'app_rtg_performance')]
    public function performance(): Response
    {
        // Performance par région pour les 10 dernières années
        $regionalPerformance = $this->rtgStatisticsRepository->getRegionalPerformanceFor10Years();
        
        // Top régions par performance
        $topRegionsAccidents = $this->rtgStatisticsRepository->getTopRegionsByAccidentsFor10Years(10);
        $topRegionsEvacuations = $this->rtgStatisticsRepository->getTopRegionsByEvacuationsFor10Years(10);
        
        // Statistiques globales
        $globalStats = $this->rtgStatisticsRepository->getGlobalStatisticsFor10Years();

        return $this->render('rtg/performance.html.twig', [
            'regionalPerformance' => $regionalPerformance,
            'topRegionsAccidents' => $topRegionsAccidents,
            'topRegionsEvacuations' => $topRegionsEvacuations,
            'globalStats' => $globalStats,
        ]);
    }

    #[Route('/export', name: 'app_rtg_export')]
    public function export(): Response
    {
        // Export des données pour les 10 dernières années
        $globalStats = $this->rtgStatisticsRepository->getGlobalStatisticsFor10Years();
        $regionalPerformance = $this->rtgStatisticsRepository->getRegionalPerformanceFor10Years();
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();

        return $this->render('rtg/export.html.twig', [
            'globalStats' => $globalStats,
            'regionalPerformance' => $regionalPerformance,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    #[Route('/api/stats', name: 'app_rtg_api_stats')]
    public function apiStats(): Response
    {
        // API pour les statistiques en temps réel
        $globalStats = $this->rtgStatisticsRepository->getGlobalStatisticsFor10Years();
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();
        
        return $this->json([
            'globalStats' => $globalStats,
            'monthlyStats' => $monthlyStats,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/regions', name: 'app_rtg_api_regions')]
    public function apiRegions(): Response
    {
        // API pour les statistiques par région
        $regionalPerformance = $this->rtgStatisticsRepository->getRegionalPerformanceFor10Years();
        $nationalEvolution = $this->rtgStatisticsRepository->getNationalEvolutionFor10Years();
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();
        
        return $this->json([
            'regionalPerformance' => $regionalPerformance,
            'nationalEvolution' => $nationalEvolution,
            'monthlyStats' => $monthlyStats,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }

    #[Route('/api/evolution', name: 'app_rtg_api_evolution')]
    public function apiEvolution(): Response
    {
        // API pour l'évolution des données
        $nationalEvolution = $this->rtgStatisticsRepository->getNationalEvolutionFor10Years();
        $monthlyStats = $this->rtgStatisticsRepository->getMonthlyStatsFor10Years();
        
        return $this->json([
            'nationalEvolution' => $nationalEvolution,
            'monthlyStats' => $monthlyStats,
            'timestamp' => new \DateTimeImmutable(),
        ]);
    }
}
